<?php

namespace App\Models;

use App\Enums\VideoProvider;
use App\Enums\WebinarPlatform;
use App\Models\Concerns\CreatesSlugRedirects;
use App\Models\Concerns\HasPrices;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Searchable;
use App\Support\Html;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A live online session hosted on Zoom / YouTube Live / Teams. The website handles
 * the event page, registration, payment, calendar invites, reminders and the recording.
 */
class Webinar extends Model
{
    use CreatesSlugRedirects, HasFactory, HasPrices, HasPublishing, HasRevisions, HasSeo, HasTaxonomies, LogsActivity, Searchable, SoftDeletes;

    /** Registrants can open the join link from this many minutes before the start. */
    public const JOIN_OPENS_MINUTES = 30;

    protected $fillable = [
        'title', 'slug', 'summary', 'description', 'featured_media_id', 'starts_at', 'duration_minutes', 'platform',
        'join_url', 'join_details', 'recording_url', 'price', 'currency', 'prices', 'free_for_members', 'capacity',
        'registration_open', 'is_featured', 'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'platform' => WebinarPlatform::class,
            'join_url' => 'encrypted',
            'join_details' => 'encrypted',
            'price' => 'integer',
            'free_for_members' => 'boolean',
            'registration_open' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function revisionIgnoredAttributes(): array
    {
        return ['registrations_count', 'views_count', 'updated_at', 'created_at', 'join_url', 'join_details', 'stripe_product_id'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(WebinarRegistration::class);
    }

    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->whereNull('canceled_at');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now()->subMinutes(180))->orderBy('starts_at');
    }

    public function endsAt(): Carbon
    {
        return $this->starts_at->copy()->addMinutes($this->duration_minutes ?: 60);
    }

    public function isLive(): bool
    {
        return now()->between($this->starts_at, $this->endsAt());
    }

    public function hasEnded(): bool
    {
        return now()->greaterThan($this->endsAt());
    }

    public function joinWindowOpen(): bool
    {
        return now()->greaterThanOrEqualTo($this->starts_at->copy()->subMinutes(self::JOIN_OPENS_MINUTES))
            && now()->lessThanOrEqualTo($this->endsAt()->addMinutes(30));
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    public function priceLabel(): string
    {
        return $this->isFree() ? 'Free' : $this->formattedPrice();
    }

    public function seatsLeft(): ?int
    {
        return $this->capacity ? max(0, $this->capacity - $this->activeRegistrations()->count()) : null;
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->seatsLeft() === 0;
    }

    public function acceptsRegistrations(): bool
    {
        return $this->registration_open && ! $this->hasEnded() && ! $this->isFull();
    }

    public function displayStart(string $format = 'l j F Y, H:i'): string
    {
        $tz = config('app.display_timezone', config('app.timezone'));

        return $this->starts_at->copy()->timezone($tz)->format($format).' '.$this->starts_at->copy()->timezone($tz)->format('T');
    }

    /** Recording player (YouTube/Vimeo) when a recording link has been added. */
    public function recordingEmbedUrl(): ?string
    {
        if (blank($this->recording_url)) {
            return null;
        }

        if (str_contains($this->recording_url, 'vimeo.com')) {
            $id = Video::parseProviderId(VideoProvider::Vimeo, $this->recording_url);
            if (! $id) {
                return null;
            }
            [$videoId, $hash] = array_pad(explode(':', $id, 2), 2, null);

            return "https://player.vimeo.com/video/{$videoId}?".http_build_query(array_filter(['h' => $hash, 'dnt' => 1]));
        }

        $id = Video::parseProviderId(VideoProvider::Youtube, $this->recording_url);

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1" : null;
    }

    public function pathForSlug(string $slug): string
    {
        return '/webinars/'.$slug;
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
            'type' => 'webinar',
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => Html::text($this->description),
            'keywords' => $this->topics->map->label()->merge($this->tags->pluck('name'))->filter()->implode(', '),
            'url' => $this->urlPath(),
            'image_url' => $this->featuredMedia?->thumbUrl(),
            'published_at' => $this->published_at,
        ];
    }
}
