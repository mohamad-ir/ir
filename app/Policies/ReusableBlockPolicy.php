<?php

namespace App\Policies;

class ReusableBlockPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'reusable_blocks.manage';
    }
}
