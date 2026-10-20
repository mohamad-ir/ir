<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/** Environment checks shown in System › Health and used by the installer. */
class SystemHealth
{
    public const EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'intl', 'fileinfo', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'curl', 'zip'];

    /** @return array<int, array{label: string, ok: bool, value: string, help?: string}> */
    public static function requirements(): array
    {
        $checks = [[
            'label' => 'PHP version', 'ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'value' => PHP_VERSION,
            'help' => 'PHP 8.3 or newer is required. Select it in DirectAdmin › Select PHP version.',
        ]];

        foreach (self::EXTENSIONS as $ext) {
            $checks[] = ['label' => "PHP extension: {$ext}", 'ok' => extension_loaded($ext), 'value' => extension_loaded($ext) ? 'enabled' : 'missing', 'help' => "Enable “{$ext}” in DirectAdmin › Select PHP version › Extensions."];
        }
        $img = extension_loaded('gd') || extension_loaded('imagick');
        $checks[] = ['label' => 'Image library (GD or Imagick)', 'ok' => $img, 'value' => extension_loaded('imagick') ? 'Imagick' : (extension_loaded('gd') ? 'GD' : 'missing'), 'help' => 'Enable gd or imagick.'];

        foreach (['storage' => storage_path(), 'bootstrap/cache' => base_path('bootstrap/cache'), 'media uploads' => public_path('media')] as $label => $dir) {
            File::ensureDirectoryExists($dir);
            $checks[] = ['label' => "Writable: {$label}", 'ok' => is_writable($dir), 'value' => is_writable($dir) ? 'writable' : 'not writable', 'help' => 'Set folder permissions to 755 (or 775) in the DirectAdmin File Manager.'];
        }

        $limit = static::bytes(ini_get('upload_max_filesize'));
        $checks[] = ['label' => 'Maximum upload size', 'ok' => $limit >= 16 * 1024 * 1024, 'value' => ini_get('upload_max_filesize'), 'help' => 'Increase upload_max_filesize and post_max_size (e.g. 64M) in PHP settings to upload large images or videos.'];
        $checks[] = ['label' => 'Memory limit', 'ok' => static::bytes(ini_get('memory_limit')) >= 128 * 1024 * 1024 || ini_get('memory_limit') === '-1', 'value' => ini_get('memory_limit'), 'help' => '256M is recommended for image processing.'];

        return $checks;
    }

    /** @return array<int, array{label: string, ok: bool, value: string, help?: string}> */
    public static function runtime(): array
    {
        $lastCron = Settings::get('system.last_cron_at');
        $cronOk = $lastCron && now()->diffInMinutes(\Illuminate\Support\Carbon::parse($lastCron)) < 10;
        try {
            $db = DB::selectOne('select version() as v')->v;
        } catch (Throwable) {
            $db = 'unavailable';
        }

        return [
            ['label' => 'Scheduled tasks (cron)', 'ok' => (bool) $cronOk, 'value' => $lastCron ? 'last run '.\Illuminate\Support\Carbon::parse($lastCron)->diffForHumans() : 'never run', 'help' => 'Add the cron job from the installation guide in DirectAdmin › Cron Jobs. Without it, scheduled newsletters and backups do not run.'],
            ['label' => 'Database server', 'ok' => $db !== 'unavailable', 'value' => (string) $db],
            ['label' => 'Debug mode', 'ok' => ! config('app.debug'), 'value' => config('app.debug') ? 'ON' : 'off', 'help' => 'Set APP_DEBUG=false in .env on the live site — debug pages reveal sensitive information.'],
            ['label' => 'Environment', 'ok' => app()->isProduction(), 'value' => app()->environment(), 'help' => 'Set APP_ENV=production in .env.'],
            ['label' => 'HTTPS', 'ok' => str_starts_with((string) config('app.url'), 'https://'), 'value' => (string) config('app.url'), 'help' => 'Enable SSL in DirectAdmin and set APP_URL to https://…'],
            ['label' => 'Pending queue jobs', 'ok' => true, 'value' => (string) (rescue(fn () => DB::table('jobs')->count(), 0, false))],
            ['label' => 'Media library size', 'ok' => true, 'value' => \Illuminate\Support\Number::fileSize(static::dirSize(public_path('media')), 1)],
            ['label' => 'Backups size', 'ok' => true, 'value' => \Illuminate\Support\Number::fileSize(static::dirSize(storage_path('app/backups')), 1)],
            ['label' => 'Application version', 'ok' => true, 'value' => config('cms.version').' · Laravel '.app()->version().' · PHP '.PHP_VERSION],
        ];
    }

    public static function bytes(string|false $value): int
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }
        $unit = strtolower(substr($value, -1));
        $n = (int) $value;

        return match ($unit) { 'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n };
    }

    public static function dirSize(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
