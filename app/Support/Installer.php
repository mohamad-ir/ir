<?php

namespace App\Support;

class Installer
{
    public static function lockFile(): string
    {
        return storage_path('app/installed.lock');
    }

    public static function isInstalled(): bool
    {
        // With a cached configuration (php artisan optimize) env() is empty, so fall back to config().
        $flag = env('APP_INSTALLED');
        if ($flag === null && app()->bound('config')) {
            $flag = config('app.installed');
        }

        return filter_var($flag, FILTER_VALIDATE_BOOL) || is_file(static::lockFile());
    }

    public static function markInstalled(): void
    {
        @mkdir(dirname(static::lockFile()), 0755, true);
        file_put_contents(static::lockFile(), json_encode(['installed_at' => now()->toIso8601String(), 'version' => config('app.version')]));
    }
}
