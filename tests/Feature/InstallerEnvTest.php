<?php

use App\Support\Installer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/*
 * bootstrap/prepare-env.php runs before Laravel on the first visit of a new installation.
 * These tests run it in a throw-away copy of the application folder layout.
 */
function envSandbox(): string
{
    $dir = sys_get_temp_dir().'/oa-env-'.uniqid();
    File::ensureDirectoryExists($dir.'/bootstrap');
    File::ensureDirectoryExists($dir.'/storage/app');
    File::copy(base_path('bootstrap/prepare-env.php'), $dir.'/bootstrap/prepare-env.php');
    File::copy(base_path('.env.example'), $dir.'/.env.example');

    return $dir;
}

function runPrepareEnv(string $dir): void
{
    Process::run([PHP_BINARY, $dir.'/bootstrap/prepare-env.php'])->throw();
}

it('creates .env from .env.example with an application key', function () {
    $dir = envSandbox();
    runPrepareEnv($dir);

    $env = File::get($dir.'/.env');
    expect($env)->toMatch('/^APP_KEY=base64:.{40,}$/m')->toContain('DB_CONNECTION=mysql')->toContain('SESSION_DRIVER=file');
    File::deleteDirectory($dir);
});

it('repairs a .env that only holds APP_KEY, keeping the existing key', function () {
    $dir = envSandbox();
    File::put($dir.'/.env', "APP_KEY=base64:EXISTINGKEYEXISTINGKEYEXISTINGKEYEXISTINGKE=\n");
    runPrepareEnv($dir);

    $env = File::get($dir.'/.env');
    expect($env)->toContain('APP_KEY=base64:EXISTINGKEYEXISTINGKEYEXISTINGKEYEXISTINGKE=')
        ->toContain('DB_CONNECTION=mysql')->toContain('SESSION_DRIVER=file')
        ->and(substr_count($env, "\nAPP_KEY=") + (str_starts_with($env, 'APP_KEY=') ? 1 : 0))->toBe(1);
    File::deleteDirectory($dir);
});

it('never adds settings to the .env of an installed site', function () {
    $dir = envSandbox();
    File::put($dir.'/storage/app/installed.lock', '{}');
    File::put($dir.'/.env', "APP_NAME=Live\nAPP_KEY=\n");
    runPrepareEnv($dir);

    $env = File::get($dir.'/.env');
    expect($env)->toMatch('/^APP_KEY=base64:.+$/m')->toContain('APP_NAME=Live')->not->toContain('DB_CONNECTION');
    File::deleteDirectory($dir);
});

it('produces one complete .env when many first requests arrive at the same time', function () {
    foreach (range(1, 5) as $round) {
        $dir = envSandbox();
        $pool = Process::pool(function ($pool) use ($dir) {
            foreach (range(1, 12) as $i) {
                $pool->command([PHP_BINARY, $dir.'/bootstrap/prepare-env.php']);
            }
        })->start()->wait();

        $env = File::get($dir.'/.env');
        expect($env)->toContain('DB_CONNECTION=mysql')->toContain('SESSION_DRIVER=file')
            ->and(preg_match_all('/^APP_KEY=base64:/m', $env))->toBe(1)
            ->and(glob($dir.'/.env.tmp-*'))->toBe([]);
        File::deleteDirectory($dir);
    }
});

it('keeps the installer independent of the database until installation is finished', function () {
    putenv('APP_INSTALLED=false');
    $_ENV['APP_INSTALLED'] = $_SERVER['APP_INSTALLED'] = 'false';
    @unlink(Installer::lockFile());

    try {
        $this->refreshApplication();
        expect(config('session.driver'))->toBe('file')
            ->and(config('cache.default'))->toBe('file')
            ->and(config('queue.default'))->toBe('sync');

        // A database default that cannot work (missing SQLite file) does not matter before installation.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => '/nonexistent/database.sqlite']);
        $this->get('/install')->assertOk()->assertSee('Welcome');
    } finally {
        putenv('APP_INSTALLED=true');
        $_ENV['APP_INSTALLED'] = $_SERVER['APP_INSTALLED'] = 'true';
        $this->refreshApplication();
    }
});
