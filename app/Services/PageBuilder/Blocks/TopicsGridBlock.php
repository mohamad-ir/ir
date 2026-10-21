<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Topic;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class TopicsGridBlock extends PageBlock
{
    public static function key(): string { return 'topics_grid'; }

    public static function label(): string { return 'Topics / diseases'; }

    public static function icon(): string { return 'heroicon-o-tag'; }

    public static function schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('eyebrow')->maxLength(80),
                TextInput::make('heading')->maxLength(160),
            ]),
            Textarea::make('intro')->rows(2),
            Select::make('topic_ids')->label('Topics')->multiple()->searchable()->options(fn () => Topic::orderBy('name')->pluck('name', 'id'))->helperText('Leave empty to show featured topics.'),
            Toggle::make('show_counts')->label('Show number of cases')->default(true),
        ];
    }

    public static function prepare(array $data): array
    {
        $ids = array_filter((array) ($data['topic_ids'] ?? []));
        $query = Topic::query()->with('media');
        $topics = $ids
            ? $query->whereIn('id', $ids)->get()->sortBy(fn ($t) => array_search($t->id, $ids))->values()
            : $query->where('is_featured', true)->orderBy('parent_id')->orderBy('sort_order')->limit(12)->get();

        // Counts include cases filed under sub-topics (e.g. Uveitis includes VKH).
        $data['topics'] = $topics->each(fn (Topic $t) => $t->cases_count = \App\Models\ClinicalCase::published()->inTopic($t)->count());

        return $data;
    }
}
