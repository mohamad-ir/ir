<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    protected $fillable = ['path_hash', 'path', 'referrer', 'hits', 'is_ignored', 'last_seen_at'];

    protected function casts(): array
    {
        return ['is_ignored' => 'boolean', 'last_seen_at' => 'datetime'];
    }
}
