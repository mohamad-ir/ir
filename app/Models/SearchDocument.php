<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchDocument extends Model
{
    protected $fillable = ['searchable_type', 'searchable_id', 'type', 'title', 'summary', 'content', 'keywords', 'url', 'image_url', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }
}
