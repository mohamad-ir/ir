<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

trait HasSeo
{
    public static function bootHasSeo(): void
    {
        static::deleting(function ($model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->seo()->delete();
            }
        });
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function seoTitle(): string
    {
        return $this->seo?->meta_title ?: $this->title;
    }

    public function seoDescription(): string
    {
        $fallback = $this->seoFallbackDescription();

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($this->seo?->meta_description ?: $fallback)))), 160);
    }

    protected function seoFallbackDescription(): ?string
    {
        return $this->summary ?? $this->excerpt ?? null;
    }
}
