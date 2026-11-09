<?php

namespace App\Policies;

class PopupPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'popups.manage';
    }
}
