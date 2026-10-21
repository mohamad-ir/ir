<?php

namespace App\Policies;

class ProfileEntryPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'profile.manage';
    }
}
