<?php

namespace App\Services\PageBuilder\Blocks;

use App\Filament\Forms\MediaPicker;
use App\Models\Video;
use App\Services\Access\ContentAccess;
use App\Services\PageBuilder\PageBlock;
use App\Support\VideoEmbed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

class VideoBlock extends PageBlock
{
    public static function key(): string { return 'video'; }

    public static function label(): string { return 'Featured video'; }

    public static function icon(): string { return 'heroicon-o-play-circle'; }

    public static function description(): string { return 'A video from the library, a YouTube/Vimeo link or an uploaded file.'; }

    public static function schema(): array
    {
        $source = fn ($get) => $get('source') ?? ($get('video_id') ? 'library' : 'url');

        return [
            TextInput::make('eyebrow')->maxLength(80),
            TextInput::make('heading')->placeholder('Uses the video title')->maxLength(160),
            Textarea::make('text')->rows(2),
            ToggleButtons::make('source')->label('Video from')->options(VideoEmbed::SOURCES)->inline()->live()
                ->default('library')->afterStateHydrated(fn ($component, $state, $get) => $component->state($state ?? ($get('video_id') ? 'library' : 'url'))),
            Select::make('video_id')->label('Video')->searchable()
                ->options(fn () => Video::latest()->limit(50)->pluck('title', 'id'))
                ->getSearchResultsUsing(fn (string $s) => Video::where('title', 'like', "%{$s}%")->limit(30)->pluck('title', 'id'))
                ->visible(fn ($get) => $source($get) === 'library')->required(fn ($get) => $source($get) === 'library'),
            TextInput::make('url')->label('YouTube or Vimeo link')->url()
                ->visible(fn ($get) => $source($get) === 'url')->required(fn ($get) => $source($get) === 'url'),
            MediaPicker::make('media_id')->label('Video file')->videosOnly()
                ->visible(fn ($get) => $source($get) === 'upload')->required(fn ($get) => $source($get) === 'upload'),
            MediaPicker::make('poster_id')->label('Cover image (optional)')->visible(fn ($get) => $source($get) !== 'library'),
        ];
    }

    public static function prepare(array $data): array
    {
        $source = $data['source'] ?? (! empty($data['video_id']) ? 'library' : 'url');
        $data['video'] = null;
        $data['embed'] = null;

        if ($source === 'library') {
            $video = Video::published()->with(['thumbnailMedia', 'videoMedia'])->find($data['video_id'] ?? null);
            $data['video'] = $video;
            $data['locked'] = $video && ! app(ContentAccess::class)->canView($video, auth()->user());
        } else {
            $data['embed'] = VideoEmbed::resolve([...$data, 'source' => $source, 'title' => ($data['heading'] ?? null) ?: 'Video']);
        }

        return $data;
    }

    public static function shouldRender(array $prepared, array $context): bool
    {
        return ($context['preview'] ?? false) || $prepared['video'] || $prepared['embed'];
    }
}
