<?php

use App\Models\Menu;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/** v1.2 on an existing site: gallery permissions and a menu link under “OCT cases”. No-op on a fresh installation. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Role::query()->exists()) {
            return;
        }

        Permissions::sync();
        $all = collect(['galleries'])->crossJoin(Permissions::CONTENT_ACTIONS)->map(fn ($p) => implode('.', $p))->all();
        Role::where('name', 'editor')->first()?->givePermissionTo($all);
        Role::where('name', 'author')->first()?->givePermissionTo($all);
        Role::where('name', 'contributor')->first()?->givePermissionTo(['galleries.view', 'galleries.create', 'galleries.update']);

        $link = ['label' => 'Image galleries', 'url' => '/galleries'];

        if ($header = Menu::where('location', 'header')->first()) {
            $items = $header->items ?? [];
            $present = collect($items)->contains(fn ($i) => ($i['url'] ?? null) === '/galleries' || collect($i['children'] ?? [])->contains('url', '/galleries'));
            if (! $present) {
                $parent = collect($items)->search(fn ($i) => ($i['url'] ?? null) === '/cases');
                if ($parent !== false) {
                    $items[$parent]['children'] = [...($items[$parent]['children'] ?? []), [...$link, 'description' => 'Curated image collections by topic']];
                } else {
                    $items[] = $link;
                }
                $header->forceFill(['items' => $items])->save();
            }
        }

        if ($footer = Menu::where('location', 'footer_primary')->first()) {
            if (! collect($footer->items)->contains('url', '/galleries')) {
                $footer->forceFill(['items' => [...($footer->items ?? []), $link]])->save();
            }
        }
    }

    public function down(): void {}
};
