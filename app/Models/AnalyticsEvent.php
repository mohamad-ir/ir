<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['name', 'subject_type', 'subject_id', 'visitor_hash', 'path', 'properties', 'created_at'];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
