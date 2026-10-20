<?php

namespace App\Models;

use App\Models\Concerns\CreatesSlugRedirects;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Searchable;
use App\Support\Html;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;

/** Hierarchical clinical topic / disease (e.g. Retina › AMD › Neovascular AMD). */
class Topic extends Model
{
    use CreatesSlugRedirects, HasSeo, LogsActivity, Searchable;

    protected $fillable = ['parent_id', 'name', 'slug', 'abbreviation', 'description', 'body', 'color', 'media_id', 'is_featured', 'sort_order'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('topics.tree'));
        static::deleted(fn () => Cache::forget('topics.tree'));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function cases(): MorphToMany
    {
        return $this->morphedByMany(ClinicalCase::class, 'topicable');
    }

    public function articles(): MorphToMany
    {
        return $this->morphedByMany(Article::class, 'topicable');
    }

    public function videos(): MorphToMany
    {
        return $this->morphedByMany(Video::class, 'topicable');
    }

    public function questions(): MorphToMany
    {
        return $this->morphedByMany(Question::class, 'topicable');
    }

    /** @return array<int, int> */
    public function descendantIdsWithSelf(): array
    {
        $all = Cache::rememberForever('topics.tree', fn () => self::query()->get(['id', 'parent_id'])->groupBy('parent_id')->map->pluck('id')->all());

        $ids = [$this->id];
        $stack = [$this->id];
        while ($stack) {
            $id = array_pop($stack);
            foreach ($all[$id] ?? [] as $child) {
                if (! in_array($child, $ids, true)) {
                    $ids[] = $child;
                    $stack[] = $child;
                }
            }
        }

        return $ids;
    }

    public function label(): string
    {
        return $this->abbreviation ? "{$this->name} ({$this->abbreviation})" : $this->name;
    }

    public function pathForSlug(string $slug): string
    {
        return '/topics/'.$slug;
    }

    protected function seoFallbackDescription(): ?string
    {
        return $this->description ?: "Ophthalmology teaching cases, articles and videos on {$this->name}.";
    }

    public function seoTitle(): string
    {
        return $this->seo?->meta_title ?: $this->label();
    }

    public function toSearchDocument(): ?array
    {
        return [
            'type' => 'topic',
            'title' => $this->label(),
            'summary' => $this->description,
            'content' => Html::text($this->body),
            'keywords' => $this->abbreviation,
            'url' => $this->urlPath(),
            'image_url' => $this->media?->thumbUrl(),
            'published_at' => $this->created_at,
        ];
    }
}
