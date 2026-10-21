<?php

namespace App\Services\Search;

use App\Models\SearchDocument;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class SearchIndexer
{
    /** Every searchable content type, used when rebuilding the whole index. */
    public const MODELS = [
        \App\Models\ClinicalCase::class, \App\Models\Article::class, \App\Models\Video::class, \App\Models\Question::class,
        \App\Models\ExamTopic::class, \App\Models\Topic::class, \App\Models\Page::class, \App\Models\Course::class, \App\Models\Webinar::class,
    ];

    public function sync(Model $model): void
    {
        try {
            $model->unsetRelation('topics')->unsetRelation('tags');
            $document = $model->toSearchDocument();

            if ($document === null) {
                $this->remove($model);

                return;
            }

            $document['title'] = mb_substr($document['title'] ?? '', 0, 250);
            $document['url'] = mb_substr($document['url'] ?? '', 0, 500);
            $document['image_url'] = isset($document['image_url']) ? mb_substr($document['image_url'], 0, 500) : null;

            SearchDocument::updateOrCreate(
                ['searchable_type' => $model->getMorphClass(), 'searchable_id' => $model->getKey()],
                $document,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function remove(Model $model): void
    {
        SearchDocument::where('searchable_type', $model->getMorphClass())->where('searchable_id', $model->getKey())->delete();
    }

    /** @param array<int, class-string<Model>> $classes */
    public function rebuild(array $classes): int
    {
        $count = 0;
        foreach ($classes as $class) {
            $class::query()->chunkById(100, function ($models) use (&$count) {
                foreach ($models as $model) {
                    $this->sync($model);
                    $count++;
                }
            });
        }

        return $count;
    }
}
