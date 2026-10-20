<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Cases\CaseResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Videos\VideoResource;
use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Question;
use App\Models\Video;
use Filament\Widgets\Widget;

/** Drafts, items awaiting review and scheduled publications across all content types. */
class EditorialPipeline extends Widget
{
    protected string $view = 'filament.widgets.pipeline';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 4];

    public function getViewData(): array
    {
        $sources = [
            [ClinicalCase::class, CaseResource::class, 'Case'],
            [Article::class, ArticleResource::class, 'Article'],
            [Video::class, VideoResource::class, 'Video'],
            [Question::class, QuestionResource::class, 'Question'],
        ];

        $items = collect();
        foreach ($sources as [$model, $resource, $label]) {
            if (! $resource::canViewAny()) {
                continue;
            }
            $model::query()->where(fn ($q) => $q->whereIn('status', ['draft', 'pending'])->orWhere(fn ($w) => $w->where('status', 'published')->where('published_at', '>', now())))
                ->latest('updated_at')->limit(6)->get()
                ->each(fn ($r) => $items->push(['label' => $label, 'title' => $r->title, 'status' => $r->displayStatus(), 'color' => $r->displayStatusColor(), 'when' => $r->isScheduled() ? $r->published_at : $r->updated_at, 'url' => $resource::getUrl('edit', ['record' => $r])]));
        }

        return ['items' => $items->sortByDesc(fn ($i) => $i['status'] === 'In review' ? PHP_INT_MAX : $i['when']->timestamp)->take(8)->values()];
    }
}
