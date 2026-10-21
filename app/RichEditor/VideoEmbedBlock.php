<?php

namespace App\RichEditor;

use App\Models\Video;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

/** Embeds a video from the video library as a privacy-friendly click-to-play card. */
class VideoEmbedBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'video';
    }

    public static function getLabel(): string
    {
        return 'Video from library';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-play-circle';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            Select::make('video_id')->label('Video')->required()->searchable()
                ->getSearchResultsUsing(fn (string $search) => Video::where('title', 'like', "%{$search}%")->limit(20)->pluck('title', 'id'))
                ->getOptionLabelUsing(fn ($value) => Video::find($value)?->title)
                ->options(fn () => Video::latest()->limit(20)->pluck('title', 'id')),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'Video: '.(Video::find($config['video_id'] ?? null)?->title ?? 'missing');
    }

    public static function toHtml(array $config, array $data): string
    {
        $video = Video::find($config['video_id'] ?? null);
        if (! $video) {
            return '';
        }

        return view('components.video-card-inline', ['video' => $video])->render();
    }
}
