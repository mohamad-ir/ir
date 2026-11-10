<?php

namespace App\Policies;

class CareerStagePolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'users.manage';
    }
}
