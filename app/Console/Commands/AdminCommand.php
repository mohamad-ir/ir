<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/** Emergency access: create a super administrator or reset a password / two-factor authentication. */
class AdminCommand extends Command
{
    protected $signature = 'cms:admin {email} {--password=} {--name=Administrator} {--reset-2fa}';

    protected $description = 'Create a super administrator, or reset the password/2FA of an existing user';

    public function handle(): int
    {
        $user = User::firstOrNew(['email' => strtolower($this->argument('email'))]);

        if ($this->option('reset-2fa')) {
            if (! $user->exists) {
                $this->error('User not found.');

                return self::FAILURE;
            }
            $user->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
            $this->info('Two-factor authentication reset. The user will set it up again at next sign-in.');

            return self::SUCCESS;
        }

        $password = $this->option('password') ?: $this->secret('New password (min. 10 characters)');
        if (strlen((string) $password) < 10) {
            $this->error('Password too short.');

            return self::FAILURE;
        }

        $user->fill(['name' => $user->name ?: $this->option('name'), 'password' => $password, 'is_active' => true]);
        $user->email_verified_at ??= now();
        $user->save();
        $user->assignRole('super_admin');
        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated').' super administrator '.$user->email);

        return self::SUCCESS;
    }
}
