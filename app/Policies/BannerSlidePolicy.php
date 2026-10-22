<?php

namespace App\Policies;

class BannerSlidePolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'design.manage';
    }
}
