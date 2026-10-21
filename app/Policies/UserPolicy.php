<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends PermissionPolicy
{
    public function update(User $user, Model $model): bool
    {
        return $user->can('users.manage') && (! $model->isSuperAdmin() || $user->isSuperAdmin());
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('users.manage') && $user->id !== $model->id && (! $model->isSuperAdmin() || $user->isSuperAdmin());
    }

    protected function permission(string $ability): string
    {
        return 'users.manage';
    }
}
