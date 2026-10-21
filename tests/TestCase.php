<?php

namespace Tests;

use App\Models\User;
use App\Support\Permissions;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Settings::flush();
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
