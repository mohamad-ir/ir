<?php

namespace App\Policies;

class EnquiryPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'enquiries.manage';
    }
}
