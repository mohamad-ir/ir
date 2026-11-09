<?php

namespace App\Services\PageBuilder\Blocks;

use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\ToggleButtons;

class SpacerBlock extends PageBlock
{
    public static function key(): string { return 'spacer'; }

    public static function label(): string { return __('Spacer / divider'); }

    public static function icon(): string { return 'heroicon-o-minus'; }

    public static function schema(): array
    {
        return [
            ToggleButtons::make('size')->inline()->options(\App\Support\Ui::options(['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']))->default('md'),
            ToggleButtons::make('style')->inline()->options(\App\Support\Ui::options(['space' => 'Empty space', 'line' => 'Line', 'dots' => 'Ornament']))->default('line'),
        ];
    }
}
