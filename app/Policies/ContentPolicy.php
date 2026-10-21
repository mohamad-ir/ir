<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared policy for editorial content. Authors and contributors may only
 * modify their own items unless they hold "content.edit_others".
 */
abstract class ContentPolicy
{
    abstract protected function prefix(): string;

    protected function owns(User $user, Model $model): bool
    {
        return ! array_key_exists('author_id', $model->getAttributes()) || (int) $model->author_id === $user->id || $user->can('content.edit_others');
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->prefix().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.update') && $this->owns($user, $model);
    }

    public function publish(User $user, ?Model $model = null): bool
    {
        return $user->can($this->prefix().'.publish') && (! $model || $this->owns($user, $model));
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.delete') && $this->owns($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->prefix().'.delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->delete($user, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $this->deleteAny($user);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.delete') && $user->can('content.edit_others');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can($this->prefix().'.delete') && $user->can('content.edit_others');
    }

    public function replicate(User $user, Model $model): bool
    {
        return $this->create($user);
    }

    public function reorder(User $user): bool
    {
        return $user->can($this->prefix().'.update');
    }
}
