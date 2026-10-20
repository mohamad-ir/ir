<?php

namespace App\Services\Theme;

use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

/**
 * Converts the design settings chosen in the dashboard into CSS custom
 * properties. Every public component reads these tokens, so changes stay
 * inside the design system and never break responsive layouts.
 */
class ThemeService
{
    public const HEADING_FONTS = [
        'newsreader' => ['Newsreader', "'Newsreader Variable', Georgia, 'Times New Roman', serif"],
        'source-serif' => ['Source Serif 4', "'Source Serif 4 Variable', Georgia, serif"],
        'literata' => ['Literata', "'Literata Variable', Georgia, serif"],
        'fraunces' => ['Fraunces', "'Fraunces Variable', Georgia, serif"],
        'inter' => ['Inter', "'Inter Variable', ui-sans-serif, system-ui, sans-serif"],
        'ibm-plex' => ['IBM Plex Sans', "'IBM Plex Sans Variable', ui-sans-serif, system-ui, sans-serif"],
        'source-sans' => ['Source Sans 3', "'Source Sans 3 Variable', ui-sans-serif, system-ui, sans-serif"],
    ];

    public const BODY_FONTS = [
        'inter' => ['Inter', "'Inter Variable', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif"],
        'source-sans' => ['Source Sans 3', "'Source Sans 3 Variable', ui-sans-serif, system-ui, sans-serif"],
        'ibm-plex' => ['IBM Plex Sans', "'IBM Plex Sans Variable', ui-sans-serif, system-ui, sans-serif"],
        'source-serif' => ['Source Serif 4', "'Source Serif 4 Variable', Georgia, serif"],
        'literata' => ['Literata', "'Literata Variable', Georgia, serif"],
    ];

    public const RADII = ['none' => ['0', '0'], 'sm' => ['.25rem', '.375rem'], 'md' => ['.5rem', '.875rem'], 'lg' => ['.75rem', '1.375rem']];

    public const DENSITY = ['compact' => ['3rem', '4rem'], 'comfortable' => ['3.75rem', '6rem'], 'spacious' => ['4.5rem', '8rem']];

    public const CONTAINERS = ['narrow' => '68rem', 'standard' => '76rem', 'wide' => '86rem'];

    public const PRESETS = [
        'clinical-navy' => ['label' => 'Clinical navy (default)', 'primary' => '#12324f', 'accent' => '#0f8b8d', 'highlight' => '#d9822b', 'background' => '#fbfaf7', 'surface' => '#ffffff', 'text' => '#1c2733'],
        'deep-retina' => ['label' => 'Deep retina', 'primary' => '#1f2a44', 'accent' => '#b4413b', 'highlight' => '#e0a33a', 'background' => '#faf8f5', 'surface' => '#ffffff', 'text' => '#20242c'],
        'macula-green' => ['label' => 'Macula green', 'primary' => '#173a36', 'accent' => '#2f8f6f', 'highlight' => '#c98a2e', 'background' => '#f8faf8', 'surface' => '#ffffff', 'text' => '#1d2b28'],
        'iris-indigo' => ['label' => 'Iris indigo', 'primary' => '#24235a', 'accent' => '#5b5fc7', 'highlight' => '#e08b4c', 'background' => '#fafafd', 'surface' => '#ffffff', 'text' => '#1f2133'],
        'graphite' => ['label' => 'Graphite & cyan', 'primary' => '#1d2329', 'accent' => '#0e7fa6', 'highlight' => '#d4a017', 'background' => '#f7f8f9', 'surface' => '#ffffff', 'text' => '#1b1f24'],
    ];

    public function css(): string
    {
        return Cache::rememberForever('theme.css', fn () => $this->build(Settings::group('theme')));
    }

    public function build(array $t): string
    {
        $color = fn (string $key) => $this->sanitizeColor($t[$key] ?? null, Settings::get("theme.$key") ?? '#000000');

        $primary = $color('primary');
        $accent = $color('accent');
        $highlight = $color('highlight');
        $bg = $color('background');
        $surface = $color('surface');
        $text = $color('text');

        $heading = self::HEADING_FONTS[$t['heading_font'] ?? ''][1] ?? self::HEADING_FONTS['newsreader'][1];
        $body = self::BODY_FONTS[$t['body_font'] ?? ''][1] ?? self::BODY_FONTS['inter'][1];
        [$rUi, $rCard] = self::RADII[$t['radius'] ?? 'md'] ?? self::RADII['md'];
        [$spaceSm, $spaceLg] = self::DENSITY[$t['density'] ?? 'comfortable'] ?? self::DENSITY['comfortable'];
        $container = self::CONTAINERS[$t['container'] ?? 'standard'] ?? self::CONTAINERS['standard'];
        $base = max(15, min(20, (int) ($t['base_size'] ?? 17)));
        $btnRadius = match ($t['button_style'] ?? 'rounded') {
            'pill' => '999px',
            'square' => '0',
            default => $rUi,
        };

        $vars = [
            '--color-ink' => $primary,
            '--color-ink-contrast' => $this->contrastText($primary),
            '--color-accent' => $accent,
            '--color-accent-contrast' => $this->contrastText($accent),
            '--color-accent-ink' => $this->ensureContrast($accent, $bg),
            '--color-highlight' => $highlight,
            '--color-highlight-contrast' => $this->contrastText($highlight),
            '--color-paper' => $bg,
            '--color-surface' => $surface,
            '--color-fg' => $text,
            '--font-heading' => $heading,
            '--font-sans' => $body,
            '--radius-ui' => $rUi,
            '--radius-card' => $rCard,
            '--radius-btn' => $btnRadius,
            '--space-section-sm' => $spaceSm,
            '--space-section' => $spaceLg,
            '--container-site' => $container,
            '--font-size-root' => ($base / 16 * 100).'%',
        ];

        $css = ':root{'.collect($vars)->map(fn ($v, $k) => "{$k}:{$v}")->implode(';').'}';

        if (filled($t['custom_css'] ?? null)) {
            $css .= "\n".str_ireplace(['</style', '<script', 'javascript:', 'expression('], '', $t['custom_css']);
        }

        return $css;
    }

    public function sanitizeColor(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
            return strtolower(strlen($value) === 4 ? '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3] : $value);
        }

        return preg_match('/^#[0-9a-f]{6}$/i', $fallback) ? $fallback : '#000000';
    }

    public function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $rgb = array_map(fn ($c) => hexdec($c) / 255, str_split($hex, 2));
        $rgb = array_map(fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $rgb);

        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
    }

    public function contrastRatio(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);

        return round((max($la, $lb) + 0.05) / (min($la, $lb) + 0.05), 2);
    }

    public function contrastText(string $hex): string
    {
        return $this->contrastRatio($hex, '#ffffff') >= $this->contrastRatio($hex, '#111111') ? '#ffffff' : '#111111';
    }

    /** Darkens a colour until it reaches WCAG AA (4.5:1) against the background, for accent-coloured text. */
    public function ensureContrast(string $hex, string $bg): string
    {
        $rgb = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        $i = 0;
        while ($this->contrastRatio($hex, $bg) < 4.5 && $i++ < 20) {
            $rgb = array_map(fn ($c) => max(0, (int) round($c * 0.9)), $rgb);
            $hex = sprintf('#%02x%02x%02x', ...$rgb);
        }

        return $hex;
    }

    /** @return array<int, string> Human readable accessibility warnings for the dashboard. */
    public function audit(array $t): array
    {
        $warnings = [];
        $bg = $this->sanitizeColor($t['background'] ?? null, '#ffffff');
        $text = $this->sanitizeColor($t['text'] ?? null, '#111111');
        $primary = $this->sanitizeColor($t['primary'] ?? null, '#12324f');

        if (($r = $this->contrastRatio($text, $bg)) < 4.5) {
            $warnings[] = "Body text contrast is {$r}:1 — below the WCAG AA minimum of 4.5:1. Choose a darker text colour or a lighter background.";
        }
        if (($r = $this->contrastRatio($primary, $bg)) < 3) {
            $warnings[] = "The primary colour has only {$r}:1 contrast with the background, so headings and buttons may be hard to see.";
        }

        return $warnings;
    }
}
