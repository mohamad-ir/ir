<?php

namespace App\Models;

use App\Enums\AccessLevel;
use App\Models\Concerns\CreatesSlugRedirects;
use App\Models\Concerns\HasComments;
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

class Article extends Model
{
    use CreatesSlugRedirects, HasComments, HasFactory, HasPublishing, HasRevisions, HasSeo, HasTaxonomies, LogsActivity, Searchable, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'category_id', 'featured_media_id', 'reading_time', 'references',
        'is_featured', 'access_level', 'comments_enabled', 'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return ['references' => 'array', 'is_featured' => 'boolean', 'access_level' => AccessLevel::class];
    }

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            $words = str_word_count(Html::text($article->body));
            $article->reading_time = max(1, (int) ceil($words / 220));
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function pathForSlug(string $slug): string
    {
        return '/articles/'.$slug;
    }

    protected function seoFallbackDescription(): ?string
    {
        return $this->excerpt ?: Html::text($this->body);
    }

    public function toSearchDocument(): ?array
    {
        if ($this->status?->value !== 'published' || $this->trashed()) {
            return null;
        }

        return [
            'type' => 'article',
            'title' => $this->title,
            'summary' => $this->excerpt,
            'content' => Html::text($this->body),
            'keywords' => $this->topics->map->label()->merge($this->tags->pluck('name'))->push($this->category?->name)->filter()->implode(', '),
            'url' => $this->urlPath(),
            'image_url' => $this->featuredMedia?->thumbUrl(),
            'published_at' => $this->published_at,
        ];
    }
}
