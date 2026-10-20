<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    use LogsActivity;

    protected $fillable = ['source_path', 'target_url', 'status_code', 'is_active', 'is_automatic', 'hits', 'last_hit_at', 'notes'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_automatic' => 'boolean', 'last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $r) {
            $r->source_path = self::normalizePath($r->source_path);
        });
        static::saved(fn () => Cache::forget('redirects.map'));
        static::deleted(fn () => Cache::forget('redirects.map'));
    }

    public static function normalizePath(string $path): string
    {
        $parts = parse_url(trim($path));
        $p = '/'.trim($parts['path'] ?? '', '/');

        return rtrim(mb_strtolower($p), '/') ?: '/';
    }
}
