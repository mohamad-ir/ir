<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PageView extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['path', 'viewable_type', 'viewable_id', 'visitor_hash', 'is_unique', 'referrer_host', 'referrer_url', 'utm_source', 'utm_medium', 'utm_campaign', 'country', 'device', 'browser', 'os', 'user_id', 'created_at'];

    protected function casts(): array
    {
        return ['is_unique' => 'boolean'];
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }
}
