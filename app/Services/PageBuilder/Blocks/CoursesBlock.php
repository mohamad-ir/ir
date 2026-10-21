<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Course;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

class CoursesBlock extends PageBlock
{
    public static function key(): string { return 'courses'; }

    public static function label(): string { return 'Courses'; }

    public static function icon(): string { return 'heroicon-o-academic-cap'; }

    public static function description(): string { return 'Featured or latest online courses with prices.'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->default('Courses')->maxLength(80),
            TextInput::make('heading')->default('Structured online courses')->maxLength(160),
            Textarea::make('intro')->rows(2),
            ToggleButtons::make('mode')->label('Show')->inline()->default('featured')->options(['featured' => 'Featured first', 'latest' => 'Latest', 'selected' => 'Choose courses'])->live(),
            Select::make('course_ids')->label('Courses')->multiple()->searchable()
                ->options(fn () => Course::published()->orderBy('title')->pluck('title', 'id'))
                ->visible(fn ($get) => $get('mode') === 'selected'),
            TextInput::make('limit')->numeric()->default(3)->minValue(1)->maxValue(12),
        ];
    }

    public static function shouldRender(array $prepared, array $context): bool
    {
        return ($context['preview'] ?? false) || collect($prepared['courses'] ?? [])->isNotEmpty();
    }

    public static function prepare(array $data): array
    {
        $query = Course::published()->with(['featuredMedia', 'lessons:id,course_id,duration_minutes']);
        $ids = array_map('intval', (array) ($data['course_ids'] ?? []));

        $data['courses'] = match ($data['mode'] ?? 'featured') {
            'selected' => $ids ? $query->whereIn('id', $ids)->get()->sortBy(fn ($c) => array_search($c->id, $ids))->values() : collect(),
            'latest' => $query->latest('published_at')->limit((int) ($data['limit'] ?? 3))->get(),
            default => $query->orderByDesc('is_featured')->latest('published_at')->limit((int) ($data['limit'] ?? 3))->get(),
        };
        $data['is_member'] = (bool) auth()->user()?->isMember();

        return $data;
    }
}
