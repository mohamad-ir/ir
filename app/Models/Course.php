<?php

namespace App\Models;

use App\Enums\Difficulty;
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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use CreatesSlugRedirects, HasFactory, HasPrices, HasPublishing, HasRevisions, HasSeo, HasTaxonomies, LogsActivity, Searchable, SoftDeletes;

    protected $attributes = ['certificate_enabled' => true, 'included_in_membership' => true];

    protected $fillable = [
        'title', 'slug', 'subtitle', 'summary', 'description', 'outcomes', 'faqs', 'level', 'featured_media_id', 'trailer_url',
        'price', 'currency', 'prices', 'included_in_membership', 'certificate_enabled', 'cpd_hours', 'is_featured', 'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return [
            'outcomes' => 'array',
            'faqs' => 'array',
            'level' => Difficulty::class,
            'price' => 'integer',
            'included_in_membership' => 'boolean',
            'certificate_enabled' => 'boolean',
            'cpd_hours' => 'float',
            'is_featured' => 'boolean',
        ];
    }

    public function revisionIgnoredAttributes(): array
    {
        return ['updated_at', 'created_at', 'views_count', 'enrollments_count', 'stripe_product_id'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order')->orderBy('id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    public function priceLabel(): string
    {
        return $this->isFree() ? 'Free' : $this->formattedPrice();
    }

    /** Lessons in curriculum order: by section, then lesson order. Lessons without a section come first. */
    public function orderedLessons()
    {
        $sectionOrder = $this->sections->pluck('sort_order', 'id');

        return $this->lessons->sortBy(fn (Lesson $l) => [
            $l->course_section_id ? 1 : 0,
            $sectionOrder[$l->course_section_id] ?? 0,
            $l->course_section_id ?? 0,
            $l->sort_order,
            $l->id,
        ])->values();
    }

    /** @return \Illuminate\Support\Collection<int, array{section: ?CourseSection, lessons: \Illuminate\Support\Collection}> */
    public function curriculum()
    {
        $lessons = $this->orderedLessons();
        $groups = collect();
        $loose = $lessons->whereNull('course_section_id');
        if ($loose->isNotEmpty()) {
            $groups->push(['section' => null, 'lessons' => $loose->values()]);
        }
        foreach ($this->sections as $section) {
            $groups->push(['section' => $section, 'lessons' => $lessons->where('course_section_id', $section->id)->values()]);
        }

        return $groups;
    }

    /** YouTube or Vimeo embed for the course introduction video. */
    public function trailerEmbedUrl(): ?string
    {
        if (blank($this->trailer_url)) {
            return null;
        }
        if (str_contains($this->trailer_url, 'vimeo.com')) {
            $id = Video::parseProviderId(\App\Enums\VideoProvider::Vimeo, $this->trailer_url);
            [$videoId, $hash] = array_pad(explode(':', (string) $id, 2), 2, null);

            return $id ? 'https://player.vimeo.com/video/'.$videoId.'?'.http_build_query(array_filter(['h' => $hash, 'dnt' => 1])) : null;
        }
        $id = Video::parseProviderId(\App\Enums\VideoProvider::Youtube, $this->trailer_url);

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1" : null;
    }

    public function totalMinutes(): int
    {
        return (int) $this->lessons->sum('duration_minutes');
    }

    public function durationLabel(): ?string
    {
        $minutes = $this->totalMinutes();
        if (! $minutes) {
            return null;
        }

        return $minutes >= 60 ? floor($minutes / 60).' h '.($minutes % 60 ? ($minutes % 60).' min' : '') : $minutes.' min';
    }

    public function pathForSlug(string $slug): string
    {
        return '/courses/'.$slug;
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
            'type' => 'course',
            'title' => $this->title,
            'summary' => $this->summary ?: $this->subtitle,
            'content' => Html::text($this->description)."\n".collect($this->outcomes)->pluck('text')->implode("\n")."\n".$this->lessons()->pluck('title')->implode("\n"),
            'keywords' => $this->topics->map->label()->merge($this->tags->pluck('name'))->filter()->implode(', '),
            'url' => $this->urlPath(),
            'image_url' => $this->featuredMedia?->thumbUrl(),
            'published_at' => $this->published_at,
        ];
    }
}
