<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\ToggleButtons;

class SpacerBlock extends PageBlock
{
    public static function key(): string { return 'spacer'; }

    public static function label(): string { return 'Spacer / divider'; }

    public static function icon(): string { return 'heroicon-o-minus'; }

    public static function schema(): array
    {
        return [
            ToggleButtons::make('size')->inline()->options(['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'])->default('md'),
            ToggleButtons::make('style')->inline()->options(['space' => 'Empty space', 'line' => 'Line', 'dots' => 'Ornament'])->default('line'),
        ];
    }
}
