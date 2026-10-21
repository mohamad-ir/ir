<?php

namespace App\Models\Concerns;

use App\Services\Search\SearchIndexer;

/**
 * Keeps the unified search_documents table in sync. Models implement
 * toSearchDocument() returning null when they must not appear in search.
 */
trait Searchable
{
    public static function bootSearchable(): void
    {
        static::saved(fn ($model) => app(SearchIndexer::class)->sync($model));
        static::deleted(fn ($model) => app(SearchIndexer::class)->remove($model));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($model) => app(SearchIndexer::class)->sync($model));
        }
    }

    abstract public function toSearchDocument(): ?array;
}
