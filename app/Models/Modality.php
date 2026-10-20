<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Imaging modality: OCT, OCT-A, FAF, FA, ICGA, fundus photography… */
class Modality extends Model
{
    protected $fillable = ['name', 'slug', 'abbreviation', 'sort_order'];

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(ClinicalCase::class);
    }

    public function label(): string
    {
        return $this->abbreviation ?: $this->name;
    }
}
