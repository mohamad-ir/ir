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
        return filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOL) || is_file(static::lockFile());
    }

    public static function markInstalled(): void
    {
        @mkdir(dirname(static::lockFile()), 0755, true);
        file_put_contents(static::lockFile(), json_encode(['installed_at' => now()->toIso8601String(), 'version' => config('app.version')]));
    }
}
