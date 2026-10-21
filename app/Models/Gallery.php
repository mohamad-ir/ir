<?php

namespace App\Models;

use App\Enums\AccessLevel;
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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A photo album on one subject (e.g. “Pachychoroid spectrum”, “ESCRS 2026”). */
class Gallery extends Model
{
    use CreatesSlugRedirects, HasFactory, HasPublishing, HasRevisions, HasSeo, HasTaxonomies, LogsActivity, Searchable, SoftDeletes;

    public const LAYOUTS = ['justified' => 'Justified rows (keeps image shapes)', 'masonry' => 'Masonry columns', 'grid' => 'Even square grid'];

    protected $fillable = [
        'title', 'slug', 'summary', 'description', 'cover_media_id', 'layout', 'dark_viewer', 'is_featured',
        'access_level', 'sort_order', 'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return ['dark_viewer' => 'boolean', 'is_featured' => 'boolean', 'access_level' => AccessLevel::class];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GalleryItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The chosen cover, or the first image of the album. */
    public function cover(): ?Media
    {
        if ($this->coverMedia) {
            return $this->coverMedia;
        }

        return $this->relationLoaded('items') ? $this->items->first()?->media : $this->items()->with('media')->first()?->media;
    }

    public function pathForSlug(string $slug): string
    {
        return '/galleries/'.$slug;
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
            'type' => 'gallery',
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => Html::text($this->description)."\n".$this->items()->pluck('caption')->filter()->implode("\n"),
            'keywords' => $this->topics->map->label()->merge($this->tags->pluck('name'))->filter()->implode(', '),
            'url' => $this->urlPath(),
            'image_url' => $this->cover()?->thumbUrl(),
            'published_at' => $this->published_at,
        ];
    }
}
