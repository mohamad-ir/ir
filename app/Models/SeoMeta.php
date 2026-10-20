<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = ['meta_title', 'meta_description', 'focus_keyword', 'canonical_url', 'og_image_id', 'noindex', 'nofollow'];

    protected function casts(): array
    {
        return ['noindex' => 'boolean', 'nofollow' => 'boolean'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }
}
