<?php

namespace App\Policies;

use App\Models\User;

class ActivityLogPolicy extends PermissionPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    protected function permission(string $ability): string
    {
        return 'activity.view';
    }
}
