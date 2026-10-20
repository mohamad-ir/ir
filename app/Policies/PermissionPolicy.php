<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Policy for resources guarded by one permission (e.g. "menus.manage"). */
abstract class PermissionPolicy
{
    abstract protected function permission(string $ability): string;

    public function viewAny(User $user): bool { return $user->can($this->permission('viewAny')); }

    public function view(User $user, Model $model): bool { return $user->can($this->permission('view')); }

    public function create(User $user): bool { return $user->can($this->permission('create')); }

    public function update(User $user, Model $model): bool { return $user->can($this->permission('update')); }

    public function delete(User $user, Model $model): bool { return $user->can($this->permission('delete')); }

    public function deleteAny(User $user): bool { return $user->can($this->permission('delete')); }

    public function restore(User $user, Model $model): bool { return $user->can($this->permission('delete')); }

    public function forceDelete(User $user, Model $model): bool { return $user->can($this->permission('delete')); }

    public function forceDeleteAny(User $user): bool { return $user->can($this->permission('delete')); }

    public function restoreAny(User $user): bool { return $user->can($this->permission('delete')); }

    public function replicate(User $user, Model $model): bool { return $user->can($this->permission('create')); }

    public function reorder(User $user): bool { return $user->can($this->permission('update')); }
}
