<?php

namespace App\Policies;

class CommentPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return 'comments.moderate';
    }
}
