<?php

namespace Tests;

use App\Models\CareerStage;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * With a cached configuration (php artisan optimize / config:cache) the test settings in phpunit.xml are
     * ignored and RefreshDatabase would wipe the real database. Stop before anything touches it.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached()) {
            throw new \RuntimeException('The configuration is cached. Run "php artisan config:clear" before running the tests, so they use the test database.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Settings::flush();
        CareerStage::flush();
    }

    protected function seedRoles(): void
    {
        Permissions::sync(true);
    }

    protected function staff(string $role = 'super_admin', array $attributes = []): User
    {
        $this->seedRoles();
        $user = User::factory()->create(['is_active' => true, ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    protected function actingAsStaff(string $role = 'super_admin'): User
    {
        $user = $this->staff($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }
}
