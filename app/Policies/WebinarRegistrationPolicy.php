<?php

namespace App\Policies;

class WebinarRegistrationPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return in_array($ability, ['viewAny', 'view'], true) ? 'sales.view' : 'sales.manage';
    }
}
