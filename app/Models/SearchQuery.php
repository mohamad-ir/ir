<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['query', 'results_count', 'visitor_hash', 'created_at'];
}
