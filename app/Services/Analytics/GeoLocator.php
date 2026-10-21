<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Country lookup. Uses the Cloudflare CF-IPCountry header when present,
 * otherwise an optional GeoLite2-Country.mmdb database uploaded in
 * Settings › Analytics (storage/app/geoip). Returns null when unavailable.
 */
class GeoLocator
{
    protected static ?Reader $reader = null;

    protected static bool $attempted = false;

    public static function databasePath(): string
    {
        return storage_path('app/geoip/GeoLite2-Country.mmdb');
    }

    public function country(Request $request): ?string
    {
        $header = strtoupper((string) $request->headers->get('CF-IPCountry'));
        if (preg_match('/^[A-Z]{2}$/', $header) && ! in_array($header, ['XX', 'T1'], true)) {
            return $header;
        }

        $reader = $this->reader();
        if (! $reader) {
            return null;
        }

        try {
            $record = $reader->get($request->ip());

            return $record['country']['iso_code'] ?? $record['registered_country']['iso_code'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function reader(): ?Reader
    {
        if (! static::$attempted) {
            static::$attempted = true;
            if (is_file(static::databasePath())) {
                try {
                    static::$reader = new Reader(static::databasePath());
                } catch (Throwable) {
                    static::$reader = null;
                }
            }
        }

        return static::$reader;
    }

    public static function available(): bool
    {
        return is_file(static::databasePath());
    }
}
