<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/** Environment checks shown in System › Health and used by the installer. */
class SystemHealth
{
    public const EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'intl', 'fileinfo', 'tokenizer', 'xml', 'ctype', 'json', 'curl', 'zip'];

    /** @return array<int, array{label: string, ok: bool, value: string, help?: string}> */
    public static function requirements(): array
    {
        $checks = [[
            'label' => __('PHP version'), 'ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'value' => PHP_VERSION,
            'help' => __('PHP 8.3 or newer is required. Select it in DirectAdmin › Select PHP version.'),
        ]];

        foreach (self::EXTENSIONS as $ext) {
            $checks[] = ['label' => __('PHP extension: :name', ['name' => $ext]), 'ok' => extension_loaded($ext), 'value' => extension_loaded($ext) ? __('enabled') : __('missing'), 'help' => __('Enable “:name” in DirectAdmin › Select PHP version › Extensions.', ['name' => $ext])];
        }
        $img = extension_loaded('gd') || extension_loaded('imagick');
        $checks[] = ['label' => __('Image library (GD or Imagick)'), 'ok' => $img, 'value' => extension_loaded('imagick') ? 'Imagick' : (extension_loaded('gd') ? 'GD' : __('missing')), 'help' => __('Enable gd or imagick.')];

        foreach (['storage' => storage_path(), 'bootstrap/cache' => base_path('bootstrap/cache'), 'media uploads' => config('filesystems.disks.public.root')] as $label => $dir) {
            File::ensureDirectoryExists($dir);
            $checks[] = ['label' => __('Writable: :folder', ['folder' => $label]), 'ok' => is_writable($dir), 'value' => is_writable($dir) ? __('writable') : __('not writable'), 'help' => __('Set folder permissions to 755 (or 775) in the DirectAdmin File Manager.')];
        }

        $limit = static::bytes(ini_get('upload_max_filesize'));
        $checks[] = ['label' => __('Maximum upload size'), 'ok' => $limit >= 16 * 1024 * 1024, 'value' => ini_get('upload_max_filesize'), 'help' => __('Increase upload_max_filesize and post_max_size (e.g. 64M) in PHP settings to upload large images or videos.')];
        $checks[] = ['label' => __('Memory limit'), 'ok' => static::bytes(ini_get('memory_limit')) >= 128 * 1024 * 1024 || ini_get('memory_limit') === '-1', 'value' => ini_get('memory_limit'), 'help' => __('256M is recommended for image processing.')];

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
            ['label' => __('Scheduled tasks (cron)'), 'ok' => (bool) $cronOk, 'value' => $lastCron ? __('last run :ago', ['ago' => \Illuminate\Support\Carbon::parse($lastCron)->diffForHumans()]) : __('never run'), 'help' => __('Add the cron job from the installation guide in DirectAdmin › Cron Jobs. Without it, scheduled newsletters and backups do not run.')],
            ['label' => __('Database server'), 'ok' => $db !== 'unavailable', 'value' => (string) $db],
            ['label' => __('Debug mode'), 'ok' => ! config('app.debug'), 'value' => config('app.debug') ? __('ON') : __('off'), 'help' => __('Set APP_DEBUG=false in .env on the live site — debug pages reveal sensitive information.')],
            ['label' => __('Environment'), 'ok' => app()->isProduction(), 'value' => app()->environment(), 'help' => __('Set APP_ENV=production in .env.')],
            ['label' => __('HTTPS'), 'ok' => str_starts_with((string) config('app.url'), 'https://'), 'value' => (string) config('app.url'), 'help' => __('Enable SSL in DirectAdmin and set APP_URL to https://…')],
            ['label' => __('Installable app (PWA)'), 'ok' => ! app(\App\Services\Pwa\PwaService::class)->enabled() || str_starts_with((string) config('app.url'), 'https://'), 'value' => app(\App\Services\Pwa\PwaService::class)->enabled() ? __('on') : __('off'), 'help' => __('Installing the app and offline reading need HTTPS: enable SSL in DirectAdmin and set APP_URL to https://…')],
            ['label' => __('Pending queue jobs'), 'ok' => true, 'value' => (string) (rescue(fn () => DB::table('jobs')->count(), 0, false))],
            ['label' => __('Media library size'), 'ok' => true, 'value' => \Illuminate\Support\Number::fileSize(static::dirSize(config('filesystems.disks.public.root')), 1)],
            ['label' => __('Backups size'), 'ok' => true, 'value' => \Illuminate\Support\Number::fileSize(static::dirSize(storage_path('app/backups')), 1)],
            ['label' => __('Application version'), 'ok' => true, 'value' => config('cms.version').' · Laravel '.app()->version().' · PHP '.PHP_VERSION],
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
