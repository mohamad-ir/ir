<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class RolePolicy extends PermissionPolicy
{
    /** Built-in roles are never deleted; a role with free access to paid content only by a super administrator. */
    public function delete(User $user, Model $model): bool
    {
        return parent::delete($user, $model)
            && ! array_key_exists($model->name, Permissions::ROLES)
            && ($user->isSuperAdmin() || $model->permissions->pluck('name')->intersect(Permissions::SUPER_ADMIN_ONLY)->isEmpty());
    }

    protected function permission(string $ability): string
    {
        return 'roles.manage';
    }
}
