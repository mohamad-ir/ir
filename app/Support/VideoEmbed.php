<?php

namespace App\Support;

use App\Enums\VideoProvider;
use App\Models\Media;
use App\Models\Video;

/**
 * Resolves a video placed inside content — a YouTube/Vimeo link, an uploaded file
 * or a video from the library — into what the player needs.
 *
 * @phpstan-type Embed array{kind: 'iframe'|'file', src: string, poster: ?string, title: string, provider: string, library: ?Video}
 */
class VideoEmbed
{
    public const SOURCES = ['url' => 'YouTube or Vimeo link', 'upload' => 'Uploaded video file', 'library' => 'Video from the video library'];

    /**
     * Player HTML for one video, respecting access: a members-only library video is shown
     * as a link card instead of a playable embed. In emails, a linked thumbnail is used.
     */
    public static function html(array $config, bool $forEmail = false): string
    {
        if (($config['source'] ?? null) === 'library' || (! isset($config['source']) && isset($config['video_id']))) {
            $video = Video::with(['thumbnailMedia', 'videoMedia'])->find($config['video_id'] ?? 0);
            if (! $video || ! $video->isPublished()) {
                return '';
            }
            if (! $forEmail && ! app(\App\Services\Access\ContentAccess::class)->canView($video, auth()->user())) {
                return view('components.video-card-inline', ['video' => $video])->render();
            }
        }

        $embed = static::resolve($config);
        if (! $embed) {
            return '';
        }

        if ($forEmail) {
            $link = $embed['library'] ? $embed['library']->url() : ($config['url'] ?? $embed['src']);

            return '<p><a href="'.e($link).'">'.($embed['poster'] ? '<img src="'.e($embed['poster']).'" alt="'.e($embed['title']).'" style="max-width:100%;border-radius:8px"><br>' : '').'▶ '.e($embed['title']).'</a></p>';
        }

        return view('components.video-embed', ['embed' => $embed, 'caption' => $config['caption'] ?? null])->render();
    }

    /** @return Embed|null */
    public static function resolve(array $config): ?array
    {
        return match ($config['source'] ?? (isset($config['video_id']) ? 'library' : 'url')) {
            'library' => static::fromLibrary(Video::with(['thumbnailMedia', 'videoMedia'])->find($config['video_id'] ?? 0), $config),
            'upload' => static::fromMedia(Media::find($config['media_id'] ?? 0), $config),
            default => static::fromUrl((string) ($config['url'] ?? ''), $config),
        };
    }

    public static function fromUrl(string $url, array $config = []): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        $title = $config['title'] ?? 'Video';
        $poster = isset($config['poster_id']) ? Media::find($config['poster_id'])?->url('lg') : null;

        if (str_contains($url, 'vimeo.com')) {
            $id = Video::parseProviderId(VideoProvider::Vimeo, $url);
            if (! $id) {
                return null;
            }
            [$videoId, $hash] = array_pad(explode(':', $id, 2), 2, null);

            return ['kind' => 'iframe', 'provider' => 'vimeo', 'title' => $title, 'poster' => $poster, 'library' => null,
                'src' => "https://player.vimeo.com/video/{$videoId}?".http_build_query(array_filter(['h' => $hash, 'dnt' => 1, 'autoplay' => 1]))];
        }

        if ($id = Video::parseProviderId(VideoProvider::Youtube, $url)) {
            return ['kind' => 'iframe', 'provider' => 'youtube', 'title' => $title, 'library' => null,
                'poster' => $poster ?? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg",
                'src' => "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1&autoplay=1"];
        }

        if (preg_match('~^https://[^\s]+\.(mp4|webm)(\?.*)?$~i', $url)) {
            return ['kind' => 'file', 'provider' => 'file', 'title' => $title, 'poster' => $poster, 'library' => null, 'src' => $url];
        }

        return null;
    }

    public static function fromMedia(?Media $media, array $config = []): ?array
    {
        if (! $media || ! $media->isVideo()) {
            return null;
        }

        return ['kind' => 'file', 'provider' => 'file', 'title' => $config['title'] ?? ($media->title ?: 'Video'), 'library' => null,
            'poster' => isset($config['poster_id']) ? Media::find($config['poster_id'])?->url('lg') : null, 'src' => $media->url()];
    }

    public static function fromLibrary(?Video $video, array $config = []): ?array
    {
        if (! $video || ! $video->isPublished() || ! ($src = $video->embedUrl())) {
            return null;
        }

        return ['kind' => $video->isFilePlayer() ? 'file' : 'iframe', 'provider' => $video->provider?->value ?? 'url',
            'title' => $config['title'] ?? $video->title, 'poster' => $video->thumbnail(), 'src' => $src, 'library' => $video];
    }
}
