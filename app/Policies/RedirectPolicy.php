<?php

namespace App\Policies;

class RedirectPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'seo.manage';
    }
}
