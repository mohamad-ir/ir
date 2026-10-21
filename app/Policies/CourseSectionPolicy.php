<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Lessons and sections follow the course permissions. */
class CourseSectionPolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return match ($ability) {
            'viewAny', 'view' => 'courses.view',
            'delete' => 'courses.delete',
            default => 'courses.update',
        };
    }

    public function create(User $user): bool
    {
        return $user->can('courses.update');
    }
}
