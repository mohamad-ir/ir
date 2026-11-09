<?php

namespace App\Services\Branding;

use App\Models\Media;
use App\Support\Settings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * One place for the site's visual identity: name, logos (for light and dark backgrounds),
 * the square site icon and the icon set generated from it (browser tab, iOS/Android home
 * screen and the future installable app / PWA).
 */
class BrandingService
{
    /** Icons generated from the square site icon: name => [size, kind]. "maskable" keeps the artwork inside the 80 % safe zone. */
    public const ICONS = [
        'favicon-32' => [32, 'any'],
        'favicon-48' => [48, 'any'],
        'apple-touch-icon' => [180, 'solid'],
        'icon-192' => [192, 'any'],
        'icon-512' => [512, 'any'],
        'icon-maskable-512' => [512, 'maskable'],
    ];

    /** Minimum size of the uploaded square site icon. */
    public const ICON_MIN_PX = 512;

    public const LOGO_HEIGHTS = ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'];

    public function name(): string
    {
        return (string) (Settings::get('site.name') ?: config('app.name'));
    }

    /** Short name for home-screen icons and the future app (≤ 12 characters display best). */
    public function shortName(): string
    {
        return (string) (Settings::get('branding.short_name') ?: $this->name());
    }

    /** Text shown next to the built-in symbol when no logo image is uploaded. */
    public function displayTitle(): string
    {
        return (string) (Settings::get('branding.logo_text') ?: $this->name());
    }

    public function logoAlt(): string
    {
        return (string) (Settings::get('branding.logo_alt') ?: $this->name());
    }

    /** @param  'light'|'dark'  $background  the background the logo sits on */
    public function logo(string $background = 'light'): ?Media
    {
        static $cache = [];
        $key = $background === 'dark' ? 'branding.logo_dark_id' : 'branding.logo_id';

        return $cache[$key.Settings::get($key)] ??= ($id = Settings::get($key)) ? Media::find($id) : null;
    }

    public function siteIcon(): ?Media
    {
        return ($id = Settings::get('branding.favicon_id')) ? Media::find($id) : null;
    }

    /** URL of a generated icon, falling back to the built-in default set. */
    public function iconUrl(string $name): string
    {
        $set = Settings::get('branding.icon_set');
        if (is_array($set) && isset($set['files'][$name])) {
            return Storage::disk('public')->url($set['files'][$name]);
        }

        return asset("icons/{$name}.png");
    }

    /** Tags for <head>: favicons, Apple touch icon and app names (public site and dashboard). */
    public function headTags(): HtmlString
    {
        $e = fn (string $v) => e($v);

        return new HtmlString(implode("\n    ", [
            '<link rel="icon" href="'.$e($this->iconUrl('favicon-32')).'" sizes="32x32" type="image/png">',
            '<link rel="icon" href="'.$e($this->iconUrl('icon-192')).'" sizes="192x192" type="image/png">',
            '<link rel="apple-touch-icon" href="'.$e($this->iconUrl('apple-touch-icon')).'">',
            '<meta name="application-name" content="'.$e($this->shortName()).'">',
            '<meta name="apple-mobile-web-app-title" content="'.$e($this->shortName()).'">',
        ]));
    }

    /** Icon list in Web App Manifest format — ready for the installable app (PWA) phase. */
    public function manifestIcons(): array
    {
        return collect(self::ICONS)->except(['favicon-32', 'favicon-48', 'apple-touch-icon'])
            ->map(fn ($spec, $name) => ['src' => $this->iconUrl($name), 'sizes' => "{$spec[0]}x{$spec[0]}", 'type' => 'image/png', 'purpose' => $spec[1] === 'maskable' ? 'maskable' : 'any'])
            ->values()->all();
    }

    /** Organization logo for Google (structured data): the square icon, else the main logo. */
    public function schemaLogo(): ?string
    {
        if (is_array(Settings::get('branding.icon_set'))) {
            return $this->iconUrl('icon-512');
        }

        return $this->logo()?->url() ?? asset('icons/icon-512.png');
    }

    /** @return array<int, string> problems with the chosen images, for the settings form */
    public function validate(array $branding): array
    {
        $errors = [];
        foreach (['logo_id' => 'Logo (light backgrounds)', 'logo_dark_id' => 'Logo (dark backgrounds)'] as $key => $label) {
            $media = filled($branding[$key] ?? null) ? Media::find($branding[$key]) : null;
            if ($media && (! $media->isImage() || $media->mime_type === 'image/gif')) {
                $errors["branding.{$key}"] = "{$label}: please use a PNG, WebP or JPG image.";
            } elseif ($media && ($media->height ?? 0) > 0 && $media->height < 32) {
                $errors["branding.{$key}"] = "{$label}: the image is only {$media->height} px tall — upload one at least 64 px tall so it stays sharp.";
            }
        }

        $icon = filled($branding['favicon_id'] ?? null) ? Media::find($branding['favicon_id']) : null;
        if ($icon) {
            if (! in_array($icon->mime_type, ['image/png', 'image/webp', 'image/jpeg'], true)) {
                $errors['branding.favicon_id'] = 'Site icon: please use a PNG (ideally with a transparent background), WebP or JPG image.';
            } elseif (! $icon->width || abs($icon->width - $icon->height) > max(2, $icon->width * 0.02)) {
                $errors['branding.favicon_id'] = "Site icon: the image must be square (this one is {$icon->width} × {$icon->height} px).";
            } elseif ($icon->width < self::ICON_MIN_PX) {
                $errors['branding.favicon_id'] = 'Site icon: at least '.self::ICON_MIN_PX." × ".self::ICON_MIN_PX." px is needed for phone home screens (this one is {$icon->width} px).";
            }
        }

        return $errors;
    }

    /** Fingerprint of the inputs, so icons are only regenerated when something changed. */
    public function iconFingerprint(): ?string
    {
        $icon = $this->siteIcon();

        return $icon ? md5($icon->id.'|'.$icon->path.'|'.$icon->updated_at?->timestamp.'|'.Settings::get('branding.icon_background')) : null;
    }

    /** Creates (or removes) the icon set from the current site icon. */
    public function syncIcons(): void
    {
        $set = Settings::get('branding.icon_set');
        $fingerprint = $this->iconFingerprint();

        if (is_array($set) && ($set['fingerprint'] ?? null) === $fingerprint) {
            return;
        }

        $new = $fingerprint ? $this->generate($this->siteIcon(), $fingerprint) : null;
        Settings::set('branding.icon_set', $new);

        if (is_array($set) && ($set['dir'] ?? null) && str_starts_with($set['dir'], 'branding/')) {
            Storage::disk('public')->deleteDirectory($set['dir']);
        }
    }

    protected function generate(Media $icon, string $fingerprint): ?array
    {
        try {
            $disk = Storage::disk($icon->disk);
            $manager = extension_loaded('imagick') && class_exists(\Imagick::class) ? new ImageManager(new ImagickDriver, decodeAnimation: false) : new ImageManager(new GdDriver, decodeAnimation: false);
            $source = $manager->read($disk->path($icon->path));
            $background = Settings::get('branding.icon_background') ?: '#ffffff';
            $dir = 'branding/icons-'.substr($fingerprint, 0, 12);
            $files = [];

            foreach (self::ICONS as $name => [$size, $kind]) {
                $art = clone $source;
                if ($kind === 'maskable') {
                    // Android crops maskable icons to circles/squircles: keep the artwork in the central 80 %.
                    $art->contain((int) round($size * 0.8), (int) round($size * 0.8), 'transparent');
                    $canvas = $manager->create($size, $size)->fill($background);
                    $canvas->place($art, 'center');
                    $art = $canvas;
                } elseif ($kind === 'solid') {
                    // iOS shows transparency as black, so the Apple icon gets the chosen background.
                    $art->contain($size, $size, 'transparent');
                    $canvas = $manager->create($size, $size)->fill($background);
                    $canvas->place($art, 'center');
                    $art = $canvas;
                } else {
                    $art->contain($size, $size, 'transparent');
                }
                Storage::disk('public')->put("{$dir}/{$name}.png", (string) $art->toPng());
                $files[$name] = "{$dir}/{$name}.png";
            }

            return ['fingerprint' => $fingerprint, 'dir' => $dir, 'files' => $files, 'generated_at' => now()->toIso8601String()];
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
