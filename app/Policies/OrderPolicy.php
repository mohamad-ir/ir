<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return in_array($ability, ['viewAny', 'view'], true) ? 'sales.view' : 'sales.manage';
    }

    /** Records come from Stripe; they are never created or deleted by hand. */
    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
