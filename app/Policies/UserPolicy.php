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

    /**
     * Not yourself, not a super administrator (unless you are one), and not someone whose membership is
     * still renewing in Stripe — cancel it first (Sales › Memberships), otherwise Stripe keeps charging.
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->can('users.manage') && $user->id !== $model->id && (! $model->isSuperAdmin() || $user->isSuperAdmin())
            && ! $model->subscriptions()->grantingAccess()->where('cancel_at_period_end', false)->exists();
    }

    protected function permission(string $ability): string
    {
        return 'users.manage';
    }
}
