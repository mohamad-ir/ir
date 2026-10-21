<?php

namespace App\Policies;

class MenuPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'menus.manage';
    }
}
