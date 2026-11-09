<?php

namespace App\Services\Pwa;

use App\Services\Branding\BrandingService;
use App\Support\Settings;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * Installable app (PWA): Web App Manifest, service-worker configuration and iOS launch screens.
 *
 * Privacy rule: only pages a guest could see are ever stored for offline use. The server marks
 * those responses (X-Offline-Cacheable, see MarkOfflineCacheable) and the service worker also
 * refuses every private area listed in PRIVATE_PATHS, so dashboard, account, checkout, sign-in,
 * API and member-only responses never reach the device cache.
 */
class PwaService
{
    /** Never cached by the service worker nor marked cacheable by the server (prefix or regex). */
    public const PRIVATE_PATHS = [
        '/account', '/checkout', '/login', '/register', '/logout', '/forgot-password', '/reset-password', '/email/',
        '/stripe', '/install', '/_preview', '/_a', '/_e', '/cron', '/color-mode', '/banner-click', '/search',
        '/newsletter/', '/comments/', '/certificates/', '/livewire', '/up',
    ];

    /** Path patterns (regular expressions) with private parts in the middle of the URL. */
    public const PRIVATE_PATTERNS = ['^/webinars/[^/]+/(join|calendar\.ics)', '^/courses/[^/]+/lessons/', '^/media/avatars/', '^/livewire[^/]*/(preview-file|upload-file|update)'];

    /** iOS launch screens (portrait, device pixels) with the CSS size and pixel ratio of each device class. */
    public const SPLASH = [
        [1320, 2868, 440, 956, 3], [1290, 2796, 430, 932, 3], [1206, 2622, 402, 874, 3], [1179, 2556, 393, 852, 3],
        [1284, 2778, 428, 926, 3], [1170, 2532, 390, 844, 3], [1125, 2436, 375, 812, 3], [1242, 2688, 414, 896, 3],
        [828, 1792, 414, 896, 2], [1242, 2208, 414, 736, 3], [750, 1334, 375, 667, 2], [640, 1136, 320, 568, 2],
        [2048, 2732, 1024, 1366, 2], [1668, 2388, 834, 1194, 2], [1640, 2360, 820, 1180, 2], [1668, 2224, 834, 1112, 2],
        [1620, 2160, 810, 1080, 2], [1488, 2266, 744, 1133, 2], [1536, 2048, 768, 1024, 2],
    ];

    public function __construct(protected BrandingService $brand) {}

    public function enabled(): bool
    {
        return (bool) Settings::get('pwa.enabled', true);
    }

    public function suggestsInstall(): bool
    {
        return $this->enabled() && (bool) Settings::get('pwa.install_prompt', true);
    }

    public function adminPrefix(): string
    {
        return '/'.trim((string) config('cms.admin_path', 'admin'), '/');
    }

    /** True when the path belongs to a private area (dashboard, account, sign-in, payments, APIs…). */
    public function isPrivatePath(string $path): bool
    {
        $path = '/'.ltrim($path, '/');
        foreach ([$this->adminPrefix(), ...self::PRIVATE_PATHS] as $prefix) {
            if ($path === rtrim($prefix, '/') || str_starts_with($path, rtrim($prefix, '/').'/') || (str_ends_with($prefix, '/') && str_starts_with($path, $prefix))) {
                return true;
            }
        }
        foreach (self::PRIVATE_PATTERNS as $pattern) {
            if (preg_match('~'.$pattern.'~', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Changes whenever anything the app shell depends on changes (release, built assets, branding,
     * theme colours, PWA settings), so installed apps pick up the new version automatically.
     */
    public function version(): string
    {
        $vite = public_path('build/manifest.json');

        return substr(md5(implode('|', [
            config('cms.version'),
            is_file($vite) ? md5_file($vite) : '',
            filemtime(resource_path('pwa/sw.js')),
            json_encode(Settings::get('branding.icon_set')),
            json_encode([Settings::get('theme.background'), Settings::get('theme.primary'), Settings::get('site.name'), Settings::get('branding.short_name')]),
            json_encode(Settings::group('pwa')),
        ])), 0, 12);
    }

    public function manifest(): array
    {
        $background = Settings::get('theme.background') ?: '#fbfaf7';

        return [
            'id' => '/',
            'name' => $this->brand->name(),
            'short_name' => $this->brand->shortName(),
            'description' => (string) (Settings::get('site.description') ?: Settings::get('site.tagline') ?: ''),
            'lang' => 'en',
            'dir' => 'ltr',
            'start_url' => '/?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'any',
            'background_color' => $background,
            'theme_color' => $background,
            'categories' => ['education', 'medical'],
            'icons' => $this->brand->manifestIcons(),
            'shortcuts' => [
                ['name' => 'OCT case library', 'short_name' => 'Cases', 'url' => '/cases?source=pwa', 'icons' => [['src' => $this->brand->iconUrl('icon-192'), 'sizes' => '192x192', 'type' => 'image/png']]],
                ['name' => 'Case of the Week', 'short_name' => 'Weekly case', 'url' => '/case-of-the-week?source=pwa'],
                ['name' => 'Video library', 'short_name' => 'Videos', 'url' => '/videos?source=pwa'],
                ['name' => 'My learning', 'short_name' => 'My learning', 'url' => '/account?source=pwa'],
            ],
            'prefer_related_applications' => false,
        ];
    }

    /** Files stored when the app is installed so the app shell and the offline page work without a connection. */
    public function precache(): array
    {
        $files = [route('pwa.offline', [], false), '/favicon.svg', '/icons/icon-192.png', '/icons/favicon-32.png'];
        $vite = public_path('build/manifest.json');
        if (is_file($vite)) {
            $manifest = json_decode((string) file_get_contents($vite), true) ?: [];
            foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
                if (isset($manifest[$entry]['file'])) {
                    $files[] = '/build/'.$manifest[$entry]['file'];
                    foreach ($manifest[$entry]['css'] ?? [] as $css) {
                        $files[] = '/build/'.$css;
                    }
                }
            }
        }
        foreach (['icon-192', 'favicon-32'] as $icon) {
            $url = parse_url($this->brand->iconUrl($icon), PHP_URL_PATH);
            if ($url) {
                $files[] = $url;
            }
        }

        return array_values(array_unique($files));
    }

    /** Configuration embedded into the service worker script. */
    public function workerConfig(): array
    {
        return [
            'version' => $this->version(),
            'enabled' => $this->enabled(),
            'offlineUrl' => route('pwa.offline', [], false),
            'precache' => $this->precache(),
            'privatePrefixes' => [$this->adminPrefix(), ...self::PRIVATE_PATHS],
            'privatePatterns' => self::PRIVATE_PATTERNS,
            'pageLimit' => 40,
            'mediaLimit' => 120,
        ];
    }

    /** <link rel="apple-touch-startup-image"> tags so iPhone and iPad show a branded launch screen. */
    public function splashLinks(): array
    {
        $v = substr(md5(json_encode(Settings::get('branding.icon_set')).Settings::get('theme.background')), 0, 8);

        return array_map(fn ($s) => [
            'href' => route('pwa.splash', ['size' => "{$s[0]}x{$s[1]}"], false).'?v='.$v,
            'media' => "(device-width: {$s[2]}px) and (device-height: {$s[3]}px) and (-webkit-device-pixel-ratio: {$s[4]}) and (orientation: portrait)",
        ], self::SPLASH);
    }

    /** Builds (once) and returns the path of a launch screen: the app icon centred on the page colour. */
    public function splash(int $width, int $height): ?string
    {
        if (! collect(self::SPLASH)->contains(fn ($s) => $s[0] === $width && $s[1] === $height)) {
            return null;
        }
        $background = Settings::get('theme.background') ?: '#fbfaf7';
        $iconUrl = $this->brand->iconUrl('icon-512');
        $set = Settings::get('branding.icon_set');
        $iconPath = is_array($set) && isset($set['files']['icon-512']) ? Storage::disk('public')->path($set['files']['icon-512']) : public_path('icons/icon-512.png');
        $key = substr(md5($iconUrl.$background), 0, 10);
        $relative = "pwa/splash-{$key}/{$width}x{$height}.png";
        $disk = Storage::disk('public');

        if (! $disk->exists($relative)) {
            $manager = extension_loaded('imagick') && class_exists(\Imagick::class) ? new ImageManager(new ImagickDriver) : new ImageManager(new GdDriver);
            $canvas = $manager->create($width, $height)->fill($background);
            $icon = $manager->read($iconPath);
            $size = (int) round(min($width, $height) * 0.28);
            $icon->contain($size, $size, 'transparent');
            $canvas->place($icon, 'center');
            $disk->put($relative, (string) $canvas->toPng());
            // Keep only the current set.
            foreach ($disk->directories('pwa') as $dir) {
                if ($dir !== "pwa/splash-{$key}") {
                    $disk->deleteDirectory($dir);
                }
            }
        }

        return $disk->path($relative);
    }
}
