<?php

namespace App\Policies;

class SubscriberPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'newsletter.manage';
    }
}
