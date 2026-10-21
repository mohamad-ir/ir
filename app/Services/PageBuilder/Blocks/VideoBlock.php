<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Video;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class VideoBlock extends PageBlock
{
    public static function key(): string { return 'video'; }

    public static function label(): string { return 'Featured video'; }

    public static function icon(): string { return 'heroicon-o-play-circle'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->maxLength(80),
            TextInput::make('heading')->placeholder('Uses the video title')->maxLength(160),
            Textarea::make('text')->rows(2),
            Select::make('video_id')->label('Video')->searchable()->required()
                ->options(fn () => Video::latest()->limit(50)->pluck('title', 'id'))
                ->getSearchResultsUsing(fn (string $s) => Video::where('title', 'like', "%{$s}%")->limit(30)->pluck('title', 'id')),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['video'] = Video::with('thumbnailMedia')->find($data['video_id'] ?? null);

        return $data;
    }
}
