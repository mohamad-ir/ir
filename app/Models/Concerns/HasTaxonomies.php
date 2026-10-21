<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTaxonomies
{
    public function topics(): MorphToMany
    {
        return $this->morphToMany(Topic::class, 'topicable')->orderBy('topics.sort_order');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function scopeInTopic(Builder $query, Topic|int $topic): Builder
    {
        $ids = $topic instanceof Topic ? $topic->descendantIdsWithSelf() : [$topic];

        return $query->whereHas('topics', fn (Builder $q) => $q->whereIn('topics.id', $ids));
    }

    /**
     * Same-type items sharing the most topics; used for "related" panels.
     */
    public function related(int $limit = 3)
    {
        $topicIds = $this->topics->pluck('id');

        $query = static::query()->published()->whereKeyNot($this->getKey());

        if ($topicIds->isNotEmpty()) {
            $query->withCount(['topics as shared_topics' => fn ($q) => $q->whereIn('topics.id', $topicIds)])
                ->orderByDesc('shared_topics');
        }

        return $query->orderByDesc('published_at')->limit($limit)->get();
    }
}
