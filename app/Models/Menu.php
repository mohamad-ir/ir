<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    use LogsActivity;

    public const LOCATIONS = [
        'header' => 'Main navigation (header)',
        'header_cta' => 'Header buttons',
        'footer_primary' => 'Footer column 1',
        'footer_secondary' => 'Footer column 2',
        'footer_tertiary' => 'Footer column 3',
        'footer_legal' => 'Footer legal links',
    ];

    protected $fillable = ['name', 'location', 'items'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('menus'));
        static::deleted(fn () => Cache::forget('menus'));
    }
}
