<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Revision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'event', 'title', 'data', 'created_at'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function revisionable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
