<?php

namespace App\RichEditor;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use App\Models\Video;
use App\Support\VideoEmbed;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

/** A video anywhere in the text: YouTube/Vimeo link, uploaded file or a video from the library. */
class VideoEmbedBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'video';
    }

    public static function getLabel(): string
    {
        return 'Video';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-play-circle';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            ToggleButtons::make('source')->label('Video from')->options(VideoEmbed::SOURCES)->default('url')->inline()->live(),
            TextInput::make('url')->label('YouTube or Vimeo link')->url()->placeholder('https://www.youtube.com/watch?v=…')
                ->visible(fn ($get) => ($get('source') ?? 'url') === 'url')->required(fn ($get) => ($get('source') ?? 'url') === 'url')
                ->helperText('Use “unlisted” on YouTube or private links on Vimeo for content you do not want listed publicly.'),
            MediaPicker::make('media_id')->label('Video file')->videosOnly()
                ->visible(fn ($get) => $get('source') === 'upload')->required(fn ($get) => $get('source') === 'upload')
                ->helperText('Keep uploads short (under ~100 MB) on shared hosting.'),
            Select::make('video_id')->label('Video')->searchable()
                ->visible(fn ($get) => $get('source') === 'library')->required(fn ($get) => $get('source') === 'library')
                ->getSearchResultsUsing(fn (string $search) => Video::where('title', 'like', "%{$search}%")->limit(20)->pluck('title', 'id'))
                ->getOptionLabelUsing(fn ($value) => Video::find($value)?->title)
                ->options(fn () => Video::latest()->limit(20)->pluck('title', 'id')),
            TextInput::make('title')->label('Title shown on the player')->maxLength(160),
            TextInput::make('caption')->maxLength(300),
            MediaPicker::make('poster_id')->label('Cover image (optional)')
                ->visible(fn ($get) => $get('source') !== 'library'),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'Video: '.match ($config['source'] ?? (isset($config['video_id']) ? 'library' : 'url')) {
            'library' => Video::find($config['video_id'] ?? null)?->title ?? 'missing',
            'upload' => Media::find($config['media_id'] ?? null)?->original_name ?? 'missing file',
            default => $config['title'] ?? $config['url'] ?? 'link',
        };
    }

    public static function toHtml(array $config, array $data): string
    {
        $keep = collect($config)->only(['source', 'url', 'media_id', 'video_id', 'title', 'caption', 'poster_id'])->filter(fn ($v) => $v !== null && $v !== '')->all();

        return '<div data-video-embed="'.base64_encode(json_encode($keep)).'"></div>';
    }
}
