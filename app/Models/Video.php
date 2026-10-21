<?php

namespace App\Models;

use App\Enums\AccessLevel;
use App\Enums\Difficulty;
use App\Enums\VideoProvider;
use App\Models\Concerns\CreatesSlugRedirects;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Searchable;
use App\Support\Html;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use CreatesSlugRedirects, HasFactory, HasPublishing, HasRevisions, HasSeo, HasTaxonomies, LogsActivity, Searchable, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'summary', 'description', 'provider', 'provider_video_id', 'video_url', 'video_media_id',
        'thumbnail_media_id', 'thumbnail_url', 'duration_seconds', 'chapters', 'transcript', 'category_id', 'level',
        'is_featured', 'access_level', 'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return [
            'chapters' => 'array',
            'is_featured' => 'boolean',
            'provider' => VideoProvider::class,
            'level' => Difficulty::class,
            'access_level' => AccessLevel::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Video $video) {
            if ($video->isDirty('video_url') && filled($video->video_url) && in_array($video->provider, [VideoProvider::Youtube, VideoProvider::Vimeo], true)) {
                $video->provider_video_id = self::parseProviderId($video->provider, $video->video_url) ?? $video->provider_video_id;
            }
        });
    }

    public static function parseProviderId(VideoProvider $provider, string $url): ?string
    {
        if ($provider === VideoProvider::Youtube) {
            if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $url, $m)) {
                return $m[1];
            }

            return preg_match('~^[A-Za-z0-9_-]{11}$~', $url) ? $url : null;
        }

        if (preg_match('~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)(?:/([a-z0-9]+))?~i', $url, $m)) {
            return $m[1].(isset($m[2]) ? ':'.$m[2] : '');
        }

        return ctype_digit($url) ? $url : null;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function thumbnailMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }

    public function videoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'video_media_id');
    }

    public function isPublicAccess(): bool
    {
        return ($this->access_level?->value ?? 'public') === 'public';
    }

    public function thumbnail(): ?string
    {
        if ($this->thumbnailMedia) {
            return $this->thumbnailMedia->url('md');
        }
        // A YouTube thumbnail URL contains the video ID, which would reveal a members-only video.
        if (! $this->isPublicAccess() && $this->provider === VideoProvider::Youtube) {
            return null;
        }
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }
        if ($this->provider === VideoProvider::Youtube && $this->provider_video_id) {
            return "https://i.ytimg.com/vi/{$this->provider_video_id}/hqdefault.jpg";
        }

        return null;
    }

    public function embedUrl(): ?string
    {
        return match ($this->provider) {
            VideoProvider::Youtube => $this->provider_video_id ? "https://www.youtube-nocookie.com/embed/{$this->provider_video_id}?enablejsapi=1&rel=0&modestbranding=1&autoplay=1" : null,
            VideoProvider::Vimeo => $this->provider_video_id ? $this->vimeoEmbedUrl() : null,
            VideoProvider::File => $this->videoMedia?->url(),
            VideoProvider::Url => $this->video_url,
            default => null,
        };
    }

    protected function vimeoEmbedUrl(): string
    {
        [$id, $hash] = array_pad(explode(':', $this->provider_video_id, 2), 2, null);

        return "https://player.vimeo.com/video/{$id}?".http_build_query(array_filter(['h' => $hash, 'dnt' => 1, 'autoplay' => 1]));
    }

    public function isFilePlayer(): bool
    {
        return in_array($this->provider, [VideoProvider::File, VideoProvider::Url], true);
    }

    public function durationLabel(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }
        $h = intdiv($this->duration_seconds, 3600);
        $m = intdiv($this->duration_seconds % 3600, 60);
        $s = $this->duration_seconds % 60;

        return $h ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    public function isoDuration(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        return 'PT'.intdiv($this->duration_seconds, 3600).'H'.intdiv($this->duration_seconds % 3600, 60).'M'.($this->duration_seconds % 60).'S';
    }

    public function pathForSlug(string $slug): string
    {
        return '/videos/'.$slug;
    }

    protected function seoFallbackDescription(): ?string
    {
        return $this->summary ?: Html::text($this->description);
    }

    public function toSearchDocument(): ?array
    {
        if ($this->status?->value !== 'published' || $this->trashed()) {
            return null;
        }

        return [
            'type' => 'video',
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => Html::text($this->description)."\n".collect($this->chapters)->pluck('title')->implode("\n")."\n".$this->transcript,
            'keywords' => $this->topics->map->label()->merge($this->tags->pluck('name'))->push($this->category?->name)->filter()->implode(', '),
            'url' => $this->urlPath(),
            'image_url' => $this->thumbnail(),
            'published_at' => $this->published_at,
        ];
    }
}
