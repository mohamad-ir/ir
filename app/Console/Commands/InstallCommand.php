<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Installer;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Console\Command;

/** SSH alternative to the web installer. */
class InstallCommand extends Command
{
    protected $signature = 'cms:install {--demo : Install sample content} {--email=} {--name=} {--password=}';

    protected $description = 'Run migrations, seed the base structure and create the first super administrator';

    public function handle(): int
    {
        if (Installer::isInstalled() && ! $this->confirm('The site is already installed. Run migrations and seeders again?', false)) {
            return self::SUCCESS;
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        if (! User::role('super_admin')->exists()) {
            $email = $this->option('email') ?: $this->ask('Administrator email');
            $name = $this->option('name') ?: $this->ask('Administrator name', 'Administrator');
            $password = $this->option('password') ?: $this->secret('Password (min. 10 characters, letters and numbers)');
            if (strlen((string) $password) < 10) {
                $this->error('Password too short.');

                return self::FAILURE;
            }
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $password, 'is_active' => true, 'email_verified_at' => now()]);
            $user->assignRole('super_admin');
            $this->info("Super administrator {$email} created.");
        }

        if ($this->option('demo')) {
            $this->call('db:seed', ['--class' => DemoContentSeeder::class, '--force' => true]);
        }

        Installer::markInstalled();
        $this->call('cms:reindex');
        $this->info('Installation complete. Sign in at '.url(config('cms.admin_path')));

        return self::SUCCESS;
    }
}
