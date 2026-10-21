<?php

namespace App\Models;

use App\Models\Concerns\CreatesSlugRedirects;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Searchable;
use App\Services\PageBuilder\BlockRegistry;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Page composed of page-builder blocks. */
class Page extends Model
{
    use CreatesSlugRedirects, HasPublishing, HasRevisions, HasSeo, LogsActivity, Searchable, SoftDeletes;

    protected $fillable = ['title', 'slug', 'template', 'blocks', 'show_title', 'summary', 'status', 'published_at', 'author_id'];

    protected function casts(): array
    {
        return ['blocks' => 'array', 'show_title' => 'boolean'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isHomepage(): bool
    {
        return (int) Settings::get('site.homepage_id') === $this->id;
    }

    public function pathForSlug(string $slug): string
    {
        return (int) Settings::get('site.homepage_id') === $this->id ? '/' : '/'.$slug;
    }

    protected function seoFallbackDescription(): ?string
    {
        return $this->summary ?: app(BlockRegistry::class)->plainText($this->blocks ?? []);
    }

    public function toSearchDocument(): ?array
    {
        if ($this->status?->value !== 'published' || $this->trashed() || $this->seo?->noindex) {
            return null;
        }

        return [
            'type' => 'page',
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => app(BlockRegistry::class)->plainText($this->blocks ?? []),
            'keywords' => null,
            'url' => $this->urlPath(),
            'published_at' => $this->published_at,
        ];
    }
}
