<?php

namespace App\Policies;

class EnrollmentPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return in_array($ability, ['viewAny', 'view'], true) ? 'sales.view' : 'sales.manage';
    }
}
