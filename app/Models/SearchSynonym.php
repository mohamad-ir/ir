<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Abbreviation / synonym dictionary used to expand search queries (CSR → central serous chorioretinopathy). */
class SearchSynonym extends Model
{
    protected $fillable = ['term', 'synonyms', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(fn (SearchSynonym $s) => $s->term = mb_strtolower(trim($s->term)));
        static::saved(fn () => Cache::forget('search.synonyms'));
        static::deleted(fn () => Cache::forget('search.synonyms'));
    }

    /** @return array<int, string> */
    public function synonymList(): array
    {
        return array_values(array_filter(array_map(fn ($s) => mb_strtolower(trim($s)), explode(',', $this->synonyms))));
    }
}
