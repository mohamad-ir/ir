<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryItem extends Model
{
    protected $fillable = ['gallery_id', 'media_id', 'caption', 'sort_order'];

    protected static function booted(): void
    {
        static::saved(fn (GalleryItem $item) => Gallery::withTrashed()->whereKey($item->gallery_id)->update(['updated_at' => now()]));
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function captionText(): ?string
    {
        return $this->caption ?: $this->media?->caption;
    }
}
