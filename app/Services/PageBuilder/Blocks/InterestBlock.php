<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Subscriber;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/**
 * "Coming soon" programme cards (courses, webinars) with a register-interest form.
 * Collects demand now; sales/enrolment can be added in a later phase.
 */
class InterestBlock extends PageBlock
{
    public static function key(): string { return 'interest'; }

    public static function label(): string { return 'Coming soon / register interest'; }

    public static function icon(): string { return 'heroicon-o-bell-alert'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->default('Coming soon')->maxLength(80),
            TextInput::make('heading')->default('Structured courses')->maxLength(160),
            Textarea::make('intro')->rows(2),
            Select::make('interest')->label('Interest list')->options(Subscriber::INTERESTS)->default('courses')->selectablePlaceholder(false),
            Repeater::make('items')->label('Programmes')->schema([
                TextInput::make('title')->required(),
                TextInput::make('meta')->placeholder('e.g. 8 modules · Self-paced'),
                Textarea::make('text')->rows(2)->columnSpanFull(),
            ])->columns(2)->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? null)->addActionLabel('Add programme'),
        ];
    }
}
