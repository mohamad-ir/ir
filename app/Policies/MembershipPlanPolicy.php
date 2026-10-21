<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MembershipPlanPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return in_array($ability, ['viewAny', 'view'], true) ? 'sales.manage' : 'sales.manage';
    }

    /** Plans with subscribers are deactivated rather than deleted. */
    public function delete(User $user, Model $model): bool
    {
        return $user->can('sales.manage') && ! $model->subscriptions()->exists();
    }
}
