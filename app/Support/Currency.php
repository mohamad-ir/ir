<?php

namespace App\Support;

use App\Services\Analytics\GeoLocator;
use Throwable;

/**
 * The visitor's display/checkout currency: their own choice (cookie), otherwise
 * a guess from their country, otherwise the site's default currency.
 */
class Currency
{
    public const COOKIE = 'currency';

    protected const BY_COUNTRY = [
        'GBP' => ['GB', 'IM', 'JE', 'GG'],
        'USD' => ['US', 'PR', 'EC', 'SV', 'PA'],
        'CHF' => ['CH', 'LI'],
        'EUR' => ['AT', 'BE', 'HR', 'CY', 'EE', 'FI', 'FR', 'DE', 'GR', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PT', 'SK', 'SI', 'ES', 'AD', 'MC', 'SM', 'VA', 'ME', 'XK'],
    ];

    public static function default(): string
    {
        return strtoupper((string) Settings::get('payments.currency', 'EUR'));
    }

    /** @return array<int, string> currencies visitors can choose, default first */
    public static function enabled(): array
    {
        $list = collect((array) Settings::get('payments.currencies', []))->map(fn ($c) => strtoupper($c))
            ->filter(fn ($c) => isset(Money::CURRENCIES[$c]));

        return $list->prepend(static::default())->unique()->values()->all();
    }

    /** Resolved once per request and remembered on the request itself. */
    public static function current(): string
    {
        $request = request();
        if ($request && ($known = $request->attributes->get('currency'))) {
            return $known;
        }

        $enabled = static::enabled();
        $chosen = strtoupper((string) $request?->cookie(self::COOKIE));
        $guess = static::forCountry(static::country());
        $currency = match (true) {
            in_array($chosen, $enabled, true) => $chosen,
            $guess && in_array($guess, $enabled, true) => $guess,
            default => static::default(),
        };
        $request?->attributes->set('currency', $currency);

        return $currency;
    }

    public static function forCountry(?string $country): ?string
    {
        foreach (self::BY_COUNTRY as $currency => $countries) {
            if (in_array(strtoupper((string) $country), $countries, true)) {
                return $currency;
            }
        }

        return null;
    }

    protected static function country(): ?string
    {
        try {
            return request() ? app(GeoLocator::class)->country(request()) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
