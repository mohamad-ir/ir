<?php

namespace App\Models;

use App\Enums\AccessLevel;
use App\Enums\Difficulty;
use App\Models\Concerns\CreatesSlugRedirects;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Searchable;
use App\Support\Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/** An OCT / retina teaching case. */
class ClinicalCase extends Model
{
    use CreatesSlugRedirects, HasComments, HasFactory, HasPublishing, HasRevisions, HasSeo, HasTaxonomies, LogsActivity, Searchable, SoftDeletes;

    /** Per-request memo for weeklyBadge(); reset whenever a case changes. */
    protected static ?int $currentWeeklyId = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::$currentWeeklyId = null);
        static::deleted(fn () => static::$currentWeeklyId = null);
    }

    protected $fillable = [
        'title', 'slug', 'summary', 'patient_age', 'patient_sex', 'eye', 'presenting_complaint', 'history',
        'findings', 'images', 'comparison', 'quiz_prompt', 'quiz_options', 'diagnosis', 'diagnosis_explanation',
        'differentials', 'discussion', 'management', 'teaching_points', 'references', 'difficulty',
        'featured_media_id', 'videos', 'comments_enabled', 'is_case_of_week', 'case_of_week_date', 'is_featured', 'access_level',
        'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'comparison' => 'array',
            'videos' => 'array',
            'quiz_options' => 'array',
            'differentials' => 'array',
            'teaching_points' => 'array',
            'references' => 'array',
            'difficulty' => Difficulty::class,
            'access_level' => AccessLevel::class,
            'is_case_of_week' => 'boolean',
            'is_featured' => 'boolean',
            'case_of_week_date' => 'date',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function modalities(): BelongsToMany
    {
        return $this->belongsToMany(Modality::class)->orderBy('sort_order');
    }

    public function scopeCaseOfWeek(Builder $query): Builder
    {
        return $query->published()->where('is_case_of_week', true)
            ->where(fn ($q) => $q->whereNull('case_of_week_date')->orWhere('case_of_week_date', '<=', today()))
            ->orderByDesc('case_of_week_date')->orderByDesc('published_at');
    }

    public static function currentCaseOfWeek(): ?self
    {
        return static::query()->caseOfWeek()->first();
    }

    /** Badge text: only the current weekly case is "Case of the week"; earlier ones show their week. */
    public function weeklyBadge(): ?string
    {
        if (! $this->is_case_of_week || $this->case_of_week_date?->isFuture()) {
            return null;
        }

        static::$currentWeeklyId ??= static::query()->caseOfWeek()->value('id') ?? 0;

        if ($this->id === static::$currentWeeklyId) {
            return 'Case of the week';
        }

        return $this->case_of_week_date ? 'Week of '.$this->case_of_week_date->format('j M') : 'Weekly case';
    }

    /** Image entries resolved to Media models: [['media' => Media, 'caption' => ..., 'label' => ...]] */
    public function imageItems(): Collection
    {
        $items = collect($this->images ?? [])->filter(fn ($i) => ! empty($i['media_id']));
        $media = Media::whereIn('id', $items->pluck('media_id'))->get()->keyBy('id');

        return $items->map(fn ($i) => [...$i, 'media' => $media->get($i['media_id'])])->filter(fn ($i) => $i['media'])->values();
    }

    public function comparisonItems(): ?array
    {
        $c = $this->comparison;
        if (empty($c['before_media_id']) || empty($c['after_media_id'])) {
            return null;
        }
        $before = Media::find($c['before_media_id']);
        $after = Media::find($c['after_media_id']);

        return $before && $after ? ['before' => $before, 'after' => $after, 'before_label' => $c['before_label'] ?? 'Before', 'after_label' => $c['after_label'] ?? 'After', 'caption' => $c['caption'] ?? null] : null;
    }

    public function coverImage(): ?Media
    {
        return $this->featuredMedia ?? $this->imageItems()->first()['media'] ?? null;
    }

    public function hasQuiz(): bool
    {
        return collect($this->quiz_options ?? [])->filter(fn ($o) => filled($o['text'] ?? null))->count() >= 2;
    }

    public function pathForSlug(string $slug): string
    {
        return '/cases/'.$slug;
    }

    protected function seoFallbackDescription(): ?string
    {
        return $this->summary ?: Html::text($this->history);
    }

    public function toSearchDocument(): ?array
    {
        if ($this->status?->value !== 'published' || $this->trashed()) {
            return null;
        }

        return [
            'type' => 'case',
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => implode("\n", array_filter([
                Html::text($this->history), Html::text($this->findings), $this->diagnosis,
                Html::text($this->diagnosis_explanation), Html::text($this->discussion),
                collect($this->differentials)->pluck('name')->implode(', '),
                collect($this->teaching_points)->pluck('point')->implode("\n"),
            ])),
            'keywords' => $this->topics->map->label()->merge($this->modalities->pluck('name'))->merge($this->tags->pluck('name'))->push($this->diagnosis)->filter()->implode(', '),
            'url' => $this->urlPath(),
            'image_url' => $this->coverImage()?->thumbUrl(),
            'published_at' => $this->published_at,
        ];
    }
}
