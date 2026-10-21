<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CtaBlock extends PageBlock
{
    public static function key(): string { return 'cta'; }

    public static function label(): string { return 'Call to action'; }

    public static function icon(): string { return 'heroicon-o-megaphone'; }

    public static function defaultTone(): string { return 'ink'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->maxLength(80),
            TextInput::make('heading')->required()->maxLength(160),
            Textarea::make('text')->rows(2)->maxLength(400),
            static::buttonsRepeater(),
        ];
    }
}
