<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Question;
use App\Models\Video;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class StatsBlock extends PageBlock
{
    public const LIVE = ['' => 'Fixed value', 'cases' => 'Live: published cases', 'articles' => 'Live: articles', 'videos' => 'Live: videos', 'questions' => 'Live: exam questions'];

    public static function key(): string { return 'stats'; }

    public static function label(): string { return 'Key figures'; }

    public static function icon(): string { return 'heroicon-o-chart-bar'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->maxLength(160),
            Repeater::make('items')->schema([
                Select::make('live')->label('Value source')->options(self::LIVE)->default(''),
                TextInput::make('value')->placeholder('e.g. 15+')->maxLength(20),
                TextInput::make('label')->required()->maxLength(80),
            ])->columns(3)->maxItems(6)->defaultItems(3)->reorderable()->addActionLabel('Add figure'),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['items'] = collect($data['items'] ?? [])->map(function ($i) {
            $count = match ($i['live'] ?? '') {
                'cases' => ClinicalCase::published()->count(),
                'articles' => Article::published()->count(),
                'videos' => Video::published()->count(),
                'questions' => Question::published()->count(),
                default => null,
            };
            if ($count !== null) {
                $i['value'] = number_format($count);
            }

            return $i;
        })->all();

        return $data;
    }
}
