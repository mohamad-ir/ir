<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class FeatureGridBlock extends PageBlock
{
    public const ICONS = [
        'eye' => 'Eye', 'academic-cap' => 'Graduation cap', 'book-open' => 'Book', 'play-circle' => 'Play',
        'beaker' => 'Research', 'light-bulb' => 'Idea / pearl', 'microphone' => 'Lecture', 'users' => 'People',
        'chat-bubble-left-right' => 'Discussion', 'clipboard-document-check' => 'Exam', 'magnifying-glass' => 'Search',
        'photo' => 'Image', 'globe-alt' => 'Global', 'heart' => 'Care', 'star' => 'Star', 'trophy' => 'Award',
        'sparkles' => 'Highlight', 'document-text' => 'Article', 'calendar-days' => 'Calendar', 'envelope' => 'Email',
        'shield-check' => 'Quality', 'chart-bar' => 'Data', 'video-camera' => 'Video', 'building-library' => 'Institution',
    ];

    public static function key(): string { return 'feature_grid'; }

    public static function label(): string { return 'Features / services'; }

    public static function icon(): string { return 'heroicon-o-view-columns'; }

    public static function schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('eyebrow')->maxLength(80),
                TextInput::make('heading')->maxLength(160),
            ]),
            Textarea::make('intro')->rows(2),
            Select::make('columns')->options([2 => '2 columns', 3 => '3 columns', 4 => '4 columns'])->default(3)->selectablePlaceholder(false),
            Repeater::make('items')->schema([
                Select::make('icon')->options(self::ICONS)->searchable()->default('eye'),
                TextInput::make('title')->required()->maxLength(100),
                Textarea::make('text')->rows(2)->maxLength(400)->columnSpanFull(),
                TextInput::make('url')->label('Link (optional)')->columnSpanFull(),
            ])->columns(2)->collapsible()->reorderable()->cloneable()->itemLabel(fn (array $state) => $state['title'] ?? null)->defaultItems(3)->addActionLabel('Add item'),
        ];
    }

    public static function plainText(array $data): string
    {
        return parent::plainText($data).' '.collect($data['items'] ?? [])->map(fn ($i) => ($i['title'] ?? '').' '.($i['text'] ?? ''))->implode(' ');
    }
}
