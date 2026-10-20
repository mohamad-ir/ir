<?php

namespace App\Models\Concerns;

use App\Models\Redirect;

/**
 * When the slug of a published item changes, a permanent redirect from the
 * old URL is created automatically so links and Google rankings survive.
 */
trait CreatesSlugRedirects
{
    public static function bootCreatesSlugRedirects(): void
    {
        static::updated(function ($model) {
            if (! $model->wasChanged('slug') || ! $model->slugRedirectsEnabled()) {
                return;
            }

            $old = $model->pathForSlug($model->getOriginal('slug'));
            $new = $model->pathForSlug($model->slug);

            if ($old === $new) {
                return;
            }

            Redirect::where('source_path', $new)->delete();
            Redirect::updateOrCreate(['source_path' => $old], [
                'target_url' => $new,
                'status_code' => 301,
                'is_active' => true,
                'is_automatic' => true,
                'notes' => 'Created automatically after a URL change.',
            ]);
            Redirect::where('target_url', $old)->update(['target_url' => $new]);
        });
    }

    public function slugRedirectsEnabled(): bool
    {
        return array_key_exists('published_at', $this->getAttributes()) ? (bool) $this->getOriginal('published_at') : true;
    }

    abstract public function pathForSlug(string $slug): string;

    public function urlPath(): string
    {
        return $this->pathForSlug($this->slug);
    }

    public function url(): string
    {
        return url($this->urlPath());
    }
}
