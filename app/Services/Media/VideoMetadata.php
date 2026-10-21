<?php

namespace App\Services\Media;

use App\Enums\VideoProvider;
use App\Models\Video;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Looks up title, thumbnail and duration through the providers' public oEmbed endpoints. */
class VideoMetadata
{
    /** @return array{title?: string, thumbnail?: string, duration?: int} */
    public function fetch(VideoProvider $provider, string $url): array
    {
        try {
            if ($provider === VideoProvider::Youtube) {
                $id = Video::parseProviderId($provider, $url);
                $data = Http::timeout(6)->get('https://www.youtube.com/oembed', ['url' => $url, 'format' => 'json'])->json() ?? [];

                return array_filter([
                    'title' => $data['title'] ?? null,
                    'thumbnail' => $id ? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg" : ($data['thumbnail_url'] ?? null),
                ]);
            }

            if ($provider === VideoProvider::Vimeo) {
                $data = Http::timeout(6)->get('https://vimeo.com/api/oembed.json', ['url' => $url])->json() ?? [];

                return array_filter([
                    'title' => $data['title'] ?? null,
                    'thumbnail' => $data['thumbnail_url'] ?? null,
                    'duration' => isset($data['duration']) ? (int) $data['duration'] : null,
                ]);
            }
        } catch (Throwable) {
            // Network unavailable: the editor can fill the fields manually.
        }

        return [];
    }
}
