<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Registered members can discuss the item. Counts only approved comments. */
trait HasComments
{
    /** Comments of an item deleted for good go with it (they stay while the item is in the bin). */
    public static function bootHasComments(): void
    {
        static::deleting(function ($model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->comments()->withTrashed()->get()->each->forceDelete();
            }
        });
    }

    public function initializeHasComments(): void
    {
        $this->mergeCasts(['comments_enabled' => 'boolean']);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /** Whether the discussion is shown at all (closed discussions stay readable). */
    public function commentsVisible(): bool
    {
        return (bool) Settings::get('comments.enabled', true)
            && in_array($this->getMorphClass(), (array) Settings::get('comments.types', ['case', 'article', 'video']), true);
    }

    /** Whether new comments are accepted on this item. */
    public function commentsOpen(): bool
    {
        return $this->commentsVisible()
            && ($this->comments_enabled ?? true)
            && (! method_exists($this, 'isPublished') || $this->isPublished());
    }

    public function refreshCommentsCount(): void
    {
        $this->forceFill(['comments_count' => $this->comments()->approved()->count()])->saveQuietly();
    }
}
