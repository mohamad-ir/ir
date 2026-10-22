<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/** v1.3 on an existing site: editors may moderate comments. No-op on a fresh installation. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Role::query()->exists()) {
            return;
        }

        Permissions::sync();
        Role::where('name', 'editor')->first()?->givePermissionTo('comments.moderate');
    }

    public function down(): void {}
};
