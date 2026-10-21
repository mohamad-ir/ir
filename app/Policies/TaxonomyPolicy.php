<?php

namespace App\Policies;

class TaxonomyPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'taxonomy.manage';
    }
}
