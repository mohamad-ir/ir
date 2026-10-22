<?php

namespace App\Policies;

class BannerPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'design.manage';
    }
}
