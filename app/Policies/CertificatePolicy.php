<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CertificatePolicy extends PermissionPolicy
{
    protected function permission(string $ability): string
    {
        return in_array($ability, ['viewAny', 'view'], true) ? 'courses.view' : 'courses.update';
    }

    /** Certificates are issued by completing a course (or from a course’s Students tab). */
    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
