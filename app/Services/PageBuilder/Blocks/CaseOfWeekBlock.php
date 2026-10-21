<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\ClinicalCase;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class CaseOfWeekBlock extends PageBlock
{
    public static function key(): string { return 'case_of_week'; }

    public static function label(): string { return 'Case of the Week'; }

    public static function icon(): string { return 'heroicon-o-academic-cap'; }

    public static function defaultTone(): string { return 'ink'; }

    public static function schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('eyebrow')->default('Case of the Week')->maxLength(80),
                TextInput::make('heading')->placeholder('Uses the case title')->maxLength(160),
            ]),
            Textarea::make('intro')->rows(2)->default('Study the images, commit to a diagnosis, then reveal the answer and teaching points.'),
            TextInput::make('button_label')->default('Attempt this case')->maxLength(60),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['case'] = ClinicalCase::currentCaseOfWeek()?->load(['featuredMedia', 'topics', 'modalities']);

        return $data;
    }
}
