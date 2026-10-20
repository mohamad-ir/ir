<?php

namespace App\Services\Seo;

use App\Models\Media;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Collects page metadata during a request and renders title, meta,
 * canonical, Open Graph/Twitter cards and JSON-LD structured data.
 */
class SeoManager
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $canonical = null;

    public ?string $image = null;

    public ?string $imageAlt = null;

    public string $type = 'website';

    public bool $noindex = false;

    public bool $nofollow = false;

    public bool $rawTitle = false;

    public ?string $publishedTime = null;

    public ?string $modifiedTime = null;

    /** @var array<int, array{name: string, url: ?string}> */
    public array $breadcrumbs = [];

    /** @var array<int, array> */
    public array $schema = [];

    public function title(?string $title, bool $raw = false): static
    {
        $this->title = $title;
        $this->rawTitle = $raw;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($description))), 160) : null;

        return $this;
    }

    public function image(Media|string|null $image, ?string $alt = null): static
    {
        if ($image instanceof Media) {
            $this->imageAlt = $alt ?? $image->altText();
            $image = $image->url('lg');
        }
        $this->image = $image ? (str_starts_with($image, 'http') ? $image : url($image)) : $this->image;
        $this->imageAlt = $alt ?? $this->imageAlt;

        return $this;
    }

    public function canonical(?string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function noindex(bool $noindex = true): static
    {
        $this->noindex = $noindex;

        return $this;
    }

    public function breadcrumb(string $name, ?string $url = null): static
    {
        $this->breadcrumbs[] = ['name' => $name, 'url' => $url];

        return $this;
    }

    public function schema(array $data): static
    {
        $this->schema[] = $data;

        return $this;
    }

    /** Applies per-item SEO overrides (meta title, description, canonical, robots, social image). */
    public function forModel(Model $model, string $type = 'article'): static
    {
        $seo = method_exists($model, 'seo') ? $model->seo : null;

        $this->title(method_exists($model, 'seoTitle') ? $model->seoTitle() : ($model->title ?? null));
        $this->description(method_exists($model, 'seoDescription') ? $model->seoDescription() : null);
        $this->type = $type;
        $this->canonical = $seo?->canonical_url ?: (method_exists($model, 'url') ? $model->url() : null);
        $this->noindex = (bool) $seo?->noindex;
        $this->nofollow = (bool) $seo?->nofollow;

        if ($seo?->ogImage) {
            $this->image($seo->ogImage);
        }

        if (isset($model->published_at)) {
            $this->publishedTime = $model->published_at?->toIso8601String();
            $this->modifiedTime = $model->updated_at?->toIso8601String();
        }

        return $this;
    }

    public function fullTitle(): string
    {
        $site = Settings::get('site.name');

        if (! $this->title) {
            return Settings::get('seo.home_title') ?: $site.' — '.Settings::get('site.tagline');
        }

        if ($this->rawTitle) {
            return $this->title;
        }

        return str_replace(['%title%', '%site%'], [$this->title, $site], Settings::get('seo.title_template') ?: '%title% | %site%');
    }

    public function metaDescription(): string
    {
        return $this->description ?: (string) Settings::get('site.description');
    }

    public function canonicalUrl(): string
    {
        $url = $this->canonical ?: url()->current();

        return rtrim(preg_replace('/\?.*/', '', $url), '/') ?: url('/');
    }

    public function shareImage(): ?string
    {
        if ($this->image) {
            return $this->image;
        }

        $media = Media::find(Settings::get('branding.og_image_id'));

        return $media?->url('lg');
    }

    public function robots(): string
    {
        $noindex = $this->noindex || Settings::get('seo.default_noindex');

        return implode(', ', [$noindex ? 'noindex' : 'index', $this->nofollow ? 'nofollow' : 'follow', 'max-image-preview:large']);
    }

    /** Site-wide structured data: WebSite with SearchAction + the physician/educator. */
    public function globalSchema(): array
    {
        $graph = [[
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => Settings::get('site.name'),
            'description' => Settings::get('site.description'),
            'inLanguage' => 'en',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ]];

        if (Settings::get('seo.schema_person')) {
            $graph[] = $this->personSchema();
        }

        return $graph;
    }

    public function personSchema(): array
    {
        $sameAs = collect(Settings::group('social'))->filter()->values()->all();
        $photo = Media::find(Settings::get('doctor.photo_id'));

        return array_filter([
            '@type' => ['Person', 'Physician'],
            '@id' => url('/').'#doctor',
            'name' => Settings::get('doctor.name'),
            'honorificSuffix' => Settings::get('doctor.credentials'),
            'jobTitle' => Settings::get('doctor.role'),
            'description' => Settings::get('doctor.short_bio'),
            'medicalSpecialty' => 'Ophthalmologic',
            'image' => $photo?->url('lg'),
            'url' => url('/'),
            'sameAs' => $sameAs ?: null,
        ]);
    }

    public function jsonLd(): string
    {
        $graph = array_merge($this->globalSchema(), $this->schema);

        if (count($this->breadcrumbs) > 0) {
            $items = [['name' => 'Home', 'url' => url('/')], ...$this->breadcrumbs];
            $graph[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($items)->values()->map(fn ($c, $i) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $c['name'],
                    'item' => $c['url'] ?? null,
                ]))->all(),
            ];
        }

        return json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }
}
