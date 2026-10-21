<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EnvWriter;
use App\Support\Installer;
use App\Support\Settings;
use App\Support\SystemHealth;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use PDO;
use Throwable;

/**
 * Browser-based installer for hosts without SSH. Reachable only until
 * installation completes (EnsureInstalled middleware), then disabled.
 */
class InstallController extends Controller
{
    public function welcome()
    {
        $checks = SystemHealth::requirements();

        return view('install.welcome', [
            'checks' => $checks,
            'blocking' => collect($checks)->reject(fn ($c) => $c['ok'] || str_contains($c['label'], 'upload') || str_contains($c['label'], 'Memory'))->isNotEmpty(),
            'envWritable' => is_writable(base_path('.env')) || (! is_file(base_path('.env')) && is_writable(base_path())),
        ]);
    }

    public function database(Request $request)
    {
        return view('install.database', ['url' => rtrim($request->root(), '/')]);
    }

    public function saveDatabase(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'app_url' => ['required', 'url'],
            'db_host' => ['required', 'string', 'max:190'],
            'db_port' => ['required', 'integer'],
            'db_database' => ['required', 'string', 'max:64'],
            'db_username' => ['required', 'string', 'max:80'],
            'db_password' => ['nullable', 'string', 'max:190'],
        ]);

        try {
            $pdo = new PDO("mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']};charset=utf8mb4", $data['db_username'], $data['db_password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
            $version = $pdo->query('select version()')->fetchColumn();
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['db_host' => 'Could not connect to the database: '.$e->getMessage()]);
        }

        try {
            EnvWriter::set([
                'APP_NAME' => $data['site_name'],
                'APP_ENV' => 'production',
                'APP_DEBUG' => false,
                'APP_URL' => rtrim($data['app_url'], '/'),
                'DB_CONNECTION' => str_contains(strtolower((string) $version), 'mariadb') ? 'mariadb' : 'mysql',
                'DB_HOST' => $data['db_host'],
                'DB_PORT' => $data['db_port'],
                'DB_DATABASE' => $data['db_database'],
                'DB_USERNAME' => $data['db_username'],
                'DB_PASSWORD' => $data['db_password'] ?? '',
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'file',
                'QUEUE_CONNECTION' => 'database',
                // When served from public_html (app outside the web root) cron jobs must know where uploads live.
                'MEDIA_ROOT' => realpath(public_path()) !== realpath(base_path('public')) ? public_path('media') : '',
            ]);

            $connection = str_contains(strtolower((string) $version), 'mariadb') ? 'mariadb' : 'mysql';
            config([
                'database.default' => $connection,
                "database.connections.{$connection}.host" => $data['db_host'],
                "database.connections.{$connection}.port" => $data['db_port'],
                "database.connections.{$connection}.database" => $data['db_database'],
                "database.connections.{$connection}.username" => $data['db_username'],
                "database.connections.{$connection}.password" => $data['db_password'] ?? '',
                'app.url' => rtrim($data['app_url'], '/'),
            ]);
            DB::purge($connection);

            @set_time_limit(300);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
            Settings::flush();
            Settings::setMany(['site.name' => $data['site_name']]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['db_host' => 'Installation step failed: '.$e->getMessage()]);
        }

        $request->session()->put('install.database', true);

        return redirect()->route('install.admin');
    }

    public function admin(Request $request)
    {
        if (! $request->session()->get('install.database')) {
            return redirect()->route('install.database');
        }

        return view('install.admin');
    }

    public function saveAdmin(Request $request)
    {
        abort_unless($request->session()->get('install.database'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'demo' => ['nullable', 'boolean'],
        ]);

        try {
            $user = User::firstOrNew(['email' => strtolower($data['email'])]);
            $user->fill(['name' => $data['name'], 'password' => $data['password'], 'is_active' => true]);
            $user->email_verified_at = now();
            $user->save();
            $user->syncRoles(['super_admin']);

            if ($request->boolean('demo')) {
                @set_time_limit(300);
                Artisan::call('db:seed', ['--class' => DemoContentSeeder::class, '--force' => true]);
            }

            Settings::set('doctor.name', $data['name']);
            Artisan::call('cms:reindex');
            Installer::markInstalled();
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['email' => 'Could not finish installation: '.$e->getMessage()]);
        }

        $request->session()->forget('install.database');

        return view('install.done', [
            'email' => $user->email,
            'adminUrl' => url(config('cms.admin_path')),
            'cronLine' => '* * * * * '.PHP_BINARY.' '.base_path('artisan').' schedule:run >> /dev/null 2>&1',
        ]);
    }

    public function done()
    {
        return redirect('/');
    }
}
