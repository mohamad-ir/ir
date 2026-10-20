<?php

namespace App\Policies;

class MediaPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return match ($ability) { 'viewAny', 'view' => 'media.view', 'create' => 'media.create', 'update' => 'media.update', default => 'media.delete' };
    }
}
