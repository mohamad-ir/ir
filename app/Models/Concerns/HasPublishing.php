<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Editorial workflow shared by every publishable content type.
 * Scheduled publishing needs no cron: an item is visible once
 * status = published AND published_at <= now().
 */
trait HasPublishing
{
    public function initializeHasPublishing(): void
    {
        $this->mergeCasts([
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ]);
    }

    public static function bootHasPublishing(): void
    {
        static::saving(function ($model) {
            if ($model->status === ContentStatus::Published && blank($model->published_at)) {
                $model->published_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), ContentStatus::Published->value)
            ->whereNotNull($this->qualifyColumn('published_at'))
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->published()->orderByDesc($this->qualifyColumn('published_at'));
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function displayStatus(): string
    {
        return $this->isScheduled() ? 'Scheduled' : ($this->status?->getLabel() ?? 'Draft');
    }

    public function displayStatusColor(): string
    {
        return $this->isScheduled() ? 'info' : ($this->status?->getColor() ?? 'gray');
    }
}
