<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Database-backed site configuration editable from the dashboard.
 * Keys are dotted ("theme.primary"); values are JSON encoded. Defaults live in SettingDefaults.
 */
class Settings
{
    protected static ?array $loaded = null;

    public static function all(): array
    {
        if (static::$loaded !== null) {
            return static::$loaded;
        }

        try {
            $stored = Cache::rememberForever('settings.all', function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::all()->mapWithKeys(function (Setting $s) {
                    $raw = $s->value;
                    if ($s->is_encrypted && $raw !== null) {
                        try {
                            $raw = Crypt::decryptString($raw);
                        } catch (Throwable) {
                            $raw = null;
                        }
                    }

                    return [$s->key => $raw === null ? null : json_decode($raw, true)];
                })->all();
            });
        } catch (Throwable) {
            $stored = [];
        }

        return static::$loaded = array_replace(SettingDefaults::flat(), $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::all();

        if (array_key_exists($key, $all)) {
            return $all[$key] ?? $default;
        }

        // Prefix lookup returns a group: Settings::get('theme') => ['primary' => ...]
        $group = [];
        foreach ($all as $k => $v) {
            if (str_starts_with($k, $key.'.')) {
                Arr::set($group, substr($k, strlen($key) + 1), $v);
            }
        }

        return $group ?: $default;
    }

    public static function set(string $key, mixed $value, bool $encrypted = false): void
    {
        $json = json_encode($value);
        Setting::updateOrCreate(['key' => $key], [
            'value' => $encrypted && $value !== null ? Crypt::encryptString($json) : $json,
            'is_encrypted' => $encrypted,
        ]);
        static::flush();
    }

    /** @param array<string, mixed> $values */
    public static function setMany(array $values, array $encryptedKeys = []): void
    {
        foreach ($values as $key => $value) {
            $json = json_encode($value);
            $encrypted = in_array($key, $encryptedKeys, true);
            Setting::updateOrCreate(['key' => $key], [
                'value' => $encrypted && $value !== null ? Crypt::encryptString($json) : $json,
                'is_encrypted' => $encrypted,
            ]);
        }
        static::flush();
    }

    /** Returns all keys under a prefix as a flat array without the prefix (for form filling). */
    public static function group(string $prefix): array
    {
        $out = [];
        foreach (static::all() as $k => $v) {
            if (str_starts_with($k, $prefix.'.')) {
                $out[substr($k, strlen($prefix) + 1)] = $v;
            }
        }

        return $out;
    }

    public static function flush(): void
    {
        static::$loaded = null;
        Cache::forget('settings.all');
        Cache::forget('theme.css');
    }
}
