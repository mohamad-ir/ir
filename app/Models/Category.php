<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const TYPES = ['article' => 'Articles', 'video' => 'Videos'];

    protected $fillable = ['type', 'name', 'slug', 'description', 'sort_order'];

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type)->orderBy('sort_order')->orderBy('name');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
