<?php

namespace App\Policies;

class RolePolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'roles.manage';
    }
}
