<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerSlide extends Model
{
    protected $fillable = [
        'banner_id', 'media_id', 'mobile_media_id', 'eyebrow', 'title', 'text', 'button_label', 'link_url', 'new_tab',
        'text_position', 'overlay', 'alt', 'starts_at', 'ends_at', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['new_tab' => 'boolean', 'is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function mobileMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'mobile_media_id');
    }

    public function isLive(): bool
    {
        return $this->is_active && $this->media
            && (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->ends_at || $this->ends_at->gt(now()));
    }

    public function scheduleLabel(): string
    {
        return match (true) {
            ! $this->is_active => 'Off',
            $this->starts_at && $this->starts_at->isFuture() => 'Starts '.$this->starts_at->format('j M'),
            $this->ends_at && $this->ends_at->isPast() => 'Ended',
            $this->ends_at !== null => 'Until '.$this->ends_at->format('j M'),
            default => 'Showing',
        };
    }

    public function isExternal(): bool
    {
        return (bool) preg_match('~^https?://~i', (string) $this->link_url)
            && parse_url((string) $this->link_url, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST);
    }

    public function href(): ?string
    {
        if (blank($this->link_url)) {
            return null;
        }

        return str_starts_with($this->link_url, '/') || preg_match('~^(https?:|mailto:|tel:)~i', $this->link_url) ? $this->link_url : '/'.ltrim($this->link_url, '/');
    }
}
