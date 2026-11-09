<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

/**
 * Language of the administration dashboard only. The website, member area and every email
 * stay in English: this locale is applied exclusively by the dashboard's own middleware.
 */
class AdminLocale
{
    public const LOCALES = ['en' => 'English', 'fa' => 'فارسی'];

    public const COOKIE = 'admin_locale';

    public static function valid(mixed $locale): ?string
    {
        return is_string($locale) && array_key_exists($locale, self::LOCALES) ? $locale : null;
    }

    /** The signed-in team member's choice, else the one remembered on this device (for the sign-in page). */
    public static function current(): string
    {
        return static::valid(auth()->user()?->admin_locale)
            ?? static::valid(request()?->cookie(self::COOKIE))
            ?? 'en';
    }

    public static function apply(string $locale): void
    {
        App::setLocale($locale);
        Carbon::setLocale($locale);

        if ($locale === 'fa') {
            // Owner's choice (Settings › General › Dashboard): right-to-left or left-to-right layout for Persian.
            $direction = Settings::get('admin.fa_direction', 'ltr') === 'rtl' ? 'rtl' : 'ltr';
            app('translator')->addLines(['layout.direction' => $direction], 'fa', 'filament-panels');
        }
    }
}
