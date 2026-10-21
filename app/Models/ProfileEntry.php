<?php

namespace App\Models;

use App\Enums\ProfileEntryType;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Structured CV entry for the About page (education, publications, lectures…). */
class ProfileEntry extends Model
{
    use LogsActivity;

    protected $fillable = ['type', 'title', 'subtitle', 'location', 'start_year', 'end_year', 'authors', 'description', 'url', 'doi', 'pmid', 'is_highlighted', 'sort_order'];

    protected function casts(): array
    {
        return ['type' => ProfileEntryType::class, 'is_highlighted' => 'boolean'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('start_year')->orderBy('id');
    }

    public function period(): ?string
    {
        if (! $this->start_year && ! $this->end_year) {
            return null;
        }
        if ($this->start_year && $this->end_year && $this->start_year !== $this->end_year) {
            return "{$this->start_year} – {$this->end_year}";
        }

        return $this->start_year ?: $this->end_year;
    }

    public function link(): ?string
    {
        if ($this->url) {
            return $this->url;
        }
        if ($this->doi) {
            return 'https://doi.org/'.ltrim(preg_replace('~^https?://(dx\.)?doi\.org/~', '', $this->doi), '/');
        }

        return $this->pmid ? 'https://pubmed.ncbi.nlm.nih.gov/'.$this->pmid.'/' : null;
    }
}
