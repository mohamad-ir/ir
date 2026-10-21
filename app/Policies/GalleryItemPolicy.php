<?php

namespace App\Policies;

use App\Models\User;

/** Gallery images follow the gallery permissions. */
class GalleryItemPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return match ($ability) {
            'viewAny', 'view' => 'galleries.view',
            'delete' => 'galleries.update',
            default => 'galleries.update',
        };
    }

    public function create(User $user): bool
    {
        return $user->can('galleries.update');
    }
}
