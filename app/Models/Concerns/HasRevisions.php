<?php

namespace App\Models\Concerns;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Stores a full snapshot of the content after each meaningful save so an
 * editor can compare versions and roll back.
 */
trait HasRevisions
{
    public static int $revisionLimit = 50;

    protected static bool $revisionsPaused = false;

    public static function bootHasRevisions(): void
    {
        static::saved(function ($model) {
            if (static::$revisionsPaused) {
                return;
            }

            $tracked = array_diff(array_keys($model->getChanges()), $model->revisionIgnoredAttributes());

            if (! $model->wasRecentlyCreated && $tracked === []) {
                return;
            }

            $model->createRevision($model->wasRecentlyCreated ? 'created' : 'updated');
        });
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }

    /** @return array<int, string> */
    public function revisionIgnoredAttributes(): array
    {
        return ['updated_at', 'created_at', 'views_count', 'reveals_count', 'guesses_count', 'correct_guesses_count', 'plays_count', 'completions_count', 'attempts_count', 'correct_count', 'comments_count'];
    }

    public function revisionSnapshot(): array
    {
        return collect($this->getAttributes())
            ->except(array_merge(['id', 'deleted_at'], $this->revisionIgnoredAttributes()))
            ->map(fn ($value, $key) => $this->isJsonCastAttribute($key) && is_string($value) ? json_decode($value, true) : $value)
            ->all();
    }

    protected function isJsonCastAttribute(string $key): bool
    {
        return in_array($this->getCasts()[$key] ?? null, ['array', 'json', 'collection', 'object'], true);
    }

    public function createRevision(string $event = 'updated'): Revision
    {
        $revision = $this->revisions()->create([
            'user_id' => auth()->id(),
            'event' => $event,
            'title' => $this->title ?? $this->name ?? null,
            'data' => $this->revisionSnapshot(),
            'created_at' => now(),
        ]);

        $keep = $this->revisions()->limit(static::$revisionLimit)->pluck('id');
        if ($keep->count() >= static::$revisionLimit) {
            $this->revisions()->whereNotIn('id', $keep)->delete();
        }

        return $revision;
    }

    public function restoreRevision(Revision $revision): void
    {
        $data = collect($revision->data)->only($this->getFillable())->all();
        $this->fill($data);
        static::withoutRevisions(fn () => $this->save());
        $this->createRevision('restored');
    }

    public static function withoutRevisions(callable $callback): mixed
    {
        static::$revisionsPaused = true;
        try {
            return $callback();
        } finally {
            static::$revisionsPaused = false;
        }
    }
}
