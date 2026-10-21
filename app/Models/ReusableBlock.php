<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** A saved group of page-builder blocks that can be placed on many pages and edited once. */
class ReusableBlock extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'description', 'blocks'];

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }
}
