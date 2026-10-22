<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Registered members can discuss the item. Counts only approved comments. */
trait HasComments
{
    public function initializeHasComments(): void
    {
        $this->mergeCasts(['comments_enabled' => 'boolean']);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /** Whether comments are shown and accepted on this item. */
    public function commentsOpen(): bool
    {
        $type = $this->getMorphClass();

        return (bool) Settings::get('comments.enabled', true)
            && in_array($type, (array) Settings::get('comments.types', ['case', 'article', 'video']), true)
            && ($this->comments_enabled ?? true);
    }

    public function refreshCommentsCount(): void
    {
        $this->forceFill(['comments_count' => $this->comments()->approved()->count()])->saveQuietly();
    }
}
