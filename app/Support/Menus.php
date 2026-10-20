<?php

namespace App\Support;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Menus
{
    /** @return array<string, array> Items by location, with resolved URLs and active state. */
    public static function all(): array
    {
        static $resolved;
        if ($resolved !== null) {
            return $resolved;
        }

        try {
            $menus = Cache::rememberForever('menus', fn () => Schema::hasTable('menus')
                ? Menu::whereNotNull('location')->get()->mapWithKeys(fn (Menu $m) => [$m->location => ['name' => $m->name, 'items' => $m->items ?? []]])->all()
                : []);
        } catch (Throwable) {
            $menus = [];
        }

        $current = '/'.trim(request()->path(), '/');

        return $resolved = collect($menus)->map(fn ($menu) => [
            'name' => $menu['name'],
            'items' => collect($menu['items'])->filter(fn ($i) => filled($i['label'] ?? null))->map(fn ($i) => static::item($i, $current))->values()->all(),
        ])->all();
    }

    protected static function item(array $item, string $current): array
    {
        $url = $item['url'] ?? '#';
        $path = '/'.trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        $isExternal = str_starts_with($url, 'http') && ! str_starts_with($url, url('/'));
        $children = collect($item['children'] ?? [])->filter(fn ($c) => filled($c['label'] ?? null))->map(fn ($c) => static::item($c, $current))->values()->all();
        $active = ! $isExternal && ($path === $current || ($path !== '/' && str_starts_with($current, $path.'/')));

        return [
            'label' => $item['label'],
            'url' => $url,
            'description' => $item['description'] ?? null,
            'new_tab' => (bool) ($item['new_tab'] ?? $isExternal),
            'style' => $item['style'] ?? null,
            'active' => $active || collect($children)->contains('active', true),
            'children' => $children,
        ];
    }
}
