<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Article;
use App\Models\Category;
use App\Models\ClinicalCase;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

/** Automatically updated list of cases, articles, videos or questions. */
class ContentFeedBlock extends PageBlock
{
    public const SOURCES = ['cases' => 'OCT cases', 'articles' => 'Articles', 'videos' => 'Videos', 'questions' => 'Exam questions'];

    public static function key(): string { return 'content_feed'; }

    public static function label(): string { return 'Content feed'; }

    public static function icon(): string { return 'heroicon-o-squares-2x2'; }

    public static function schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('eyebrow')->maxLength(80),
                TextInput::make('heading')->maxLength(160),
            ]),
            Textarea::make('intro')->rows(2)->maxLength(400),
            Grid::make(3)->schema([
                Select::make('source')->options(self::SOURCES)->default('cases')->required()->live()->selectablePlaceholder(false),
                Select::make('mode')->label('Show')->options(['latest' => 'Latest', 'featured' => 'Featured first', 'popular' => 'Most viewed'])->default('latest')->selectablePlaceholder(false),
                Select::make('limit')->label('Number of items')->options([2 => 2, 3 => 3, 4 => 4, 6 => 6, 8 => 8, 9 => 9, 12 => 12])->default(3)->selectablePlaceholder(false),
            ]),
            Grid::make(2)->schema([
                Select::make('topic_id')->label('Only this topic')->options(fn () => Topic::orderBy('name')->pluck('name', 'id'))->searchable()->placeholder('All topics'),
                Select::make('category_id')->label('Only this category')
                    ->options(fn (Get $get) => Category::ofType($get('source') === 'videos' ? 'video' : 'article')->pluck('name', 'id'))
                    ->visible(fn (Get $get) => in_array($get('source'), ['articles', 'videos']))->placeholder('All categories'),
            ]),
            ToggleButtons::make('layout')->inline()->options(['cards' => 'Cards', 'list' => 'List', 'feature' => 'Large first item'])->default('cards'),
            Grid::make(2)->schema([
                TextInput::make('view_all_label')->label('“View all” label')->placeholder('Browse all cases'),
                TextInput::make('view_all_url')->label('“View all” link')->placeholder('/cases'),
            ]),
        ];
    }

    public static function prepare(array $data): array
    {
        $limit = max(1, min(12, (int) ($data['limit'] ?? 3)));
        $source = $data['source'] ?? 'cases';
        $model = match ($source) {
            'articles' => Article::class,
            'videos' => Video::class,
            'questions' => Question::class,
            default => ClinicalCase::class,
        };

        $query = $model::query()->published()->with(match ($source) {
            'articles' => ['featuredMedia', 'category', 'topics', 'author'],
            'videos' => ['thumbnailMedia', 'category', 'topics'],
            'questions' => ['examTopic', 'media'],
            default => ['featuredMedia', 'topics', 'modalities'],
        });

        if (! empty($data['topic_id']) && ($topic = Topic::find($data['topic_id']))) {
            $query->inTopic($topic);
        }
        if (! empty($data['category_id']) && in_array($source, ['articles', 'videos'])) {
            $query->where('category_id', $data['category_id']);
        }

        match ($data['mode'] ?? 'latest') {
            'featured' => $source !== 'questions' ? $query->orderByDesc('is_featured')->orderByDesc('published_at') : $query->latest('published_at'),
            'popular' => $query->orderByDesc($source === 'questions' ? 'attempts_count' : 'views_count'),
            default => $query->orderByDesc('published_at'),
        };

        $data['items'] = $query->limit($limit)->get();
        $data['source'] = $source;
        $data['view_all_url'] = $data['view_all_url'] ?? null;

        return $data;
    }
}
