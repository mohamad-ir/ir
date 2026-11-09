<?php

namespace App\Support;

/**
 * Light / Dark / System display mode for the public website.
 *
 * Resolution order: the site default when visitors may not choose, otherwise the signed-in
 * member's saved choice, then the visitor's cookie, then the site default. "system" is
 * resolved in the browser (prefers-color-scheme) by the inline script in layouts/app.
 */
class ColorMode
{
    public const MODES = ['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'];

    public const COOKIE = 'color_mode';

    public static function switchable(): bool
    {
        return (bool) Settings::get('theme.color_mode_switch', true);
    }

    public static function default(): string
    {
        return static::valid(Settings::get('theme.color_mode')) ?? 'system';
    }

    public static function current(): string
    {
        $request = request();

        // Dashboard theme preview can force a mode.
        if ($request?->query('theme_preview') && ($forced = static::valid($request->query('color_mode')))) {
            return $forced;
        }

        if (! static::switchable()) {
            return static::default();
        }

        return static::valid(auth()->user()?->color_mode)
            ?? static::valid($request?->cookie(self::COOKIE))
            ?? static::default();
    }

    public static function valid(mixed $mode): ?string
    {
        return is_string($mode) && array_key_exists($mode, self::MODES) ? $mode : null;
    }
}
