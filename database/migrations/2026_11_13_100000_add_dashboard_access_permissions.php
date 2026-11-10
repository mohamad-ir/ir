<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Dashboard access becomes a permission ("dashboard.access"), held by the five built-in team roles,
 * and custom roles can be given free access to paid content ("content.free_access", off by default).
 * Nothing changes for existing users: the built-in roles keep exactly the access they had, and
 * existing custom roles stay outside the dashboard until "Sign in to the dashboard" is ticked.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Role::query()->exists()) {
            return; // fresh installation: the roles seeder creates everything
        }

        Permissions::sync();
    }

    public function down(): void {}
};
