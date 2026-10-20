<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class TimelineBlock extends PageBlock
{
    public static function key(): string { return 'timeline'; }

    public static function label(): string { return 'Timeline'; }

    public static function icon(): string { return 'heroicon-o-clock'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->maxLength(160),
            Textarea::make('intro')->rows(2),
            Repeater::make('items')->schema([
                TextInput::make('period')->placeholder('2015 – 2018')->maxLength(40),
                TextInput::make('title')->required()->maxLength(160),
                TextInput::make('subtitle')->maxLength(160),
                Textarea::make('text')->rows(2)->columnSpanFull(),
            ])->columns(3)->reorderable()->collapsible()->cloneable()->itemLabel(fn (array $state) => trim(($state['period'] ?? '').' '.($state['title'] ?? '')) ?: null)->addActionLabel('Add milestone'),
        ];
    }
}
