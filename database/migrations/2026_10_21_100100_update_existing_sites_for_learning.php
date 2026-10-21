<?php

use App\Enums\ContentStatus;
use App\Models\Menu;
use App\Models\Page;
use App\Support\LegalTemplates;
use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Brings an existing installation up to date for courses, webinars and membership:
 * permissions, navigation, legal pages and the old "courses coming soon" page.
 * Fresh installations get the same through the seeders, so every step is a no-op on empty tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('pages')) {
            return;
        }

        if (Role::query()->exists()) {
            Permissions::sync();
            $editor = Role::where('name', 'editor')->first();
            $editor?->givePermissionTo(collect(['courses', 'webinars'])->crossJoin(Permissions::CONTENT_ACTIONS)->map(fn ($p) => implode('.', $p))->push('sales.view')->all());
        }

        if (! Page::query()->exists()) {
            return; // fresh installation: the seeders create everything
        }

        Page::withoutRevisions(function () {
            // The built-in course catalogue now lives at /courses.
            $placeholder = Page::where('slug', 'courses')->first();
            if ($placeholder) {
                $placeholder->forceFill(['slug' => 'courses-coming-soon', 'status' => ContentStatus::Draft])->saveQuietly();
            }

            // Replace the homepage's "courses coming soon" teaser with the live courses + webinars sections.
            foreach (Page::all() as $page) {
                $blocks = $page->blocks ?? [];
                $changed = false;
                $out = [];
                foreach ($blocks as $block) {
                    if (($block['type'] ?? null) === 'interest' && ($block['data']['interest'] ?? null) === 'courses' && ($block['data']['eyebrow'] ?? '') === 'Coming soon') {
                        $out[] = ['type' => 'courses', 'data' => ['eyebrow' => 'Courses', 'heading' => 'Structured online courses', 'mode' => 'featured', 'limit' => 3]];
                        $out[] = ['type' => 'webinars', 'data' => ['eyebrow' => 'Live teaching', 'heading' => 'Upcoming webinars', 'limit' => 2, '_tone' => 'muted']];
                        $changed = true;
                    } else {
                        $out[] = $block;
                    }
                }
                if ($changed) {
                    $page->forceFill(['blocks' => $out])->saveQuietly();
                }
            }

            foreach (['terms-of-sale' => ['Terms of sale', LegalTemplates::termsOfSale()], 'refund-policy' => ['Refund policy', LegalTemplates::refundPolicy()]] as $slug => [$title, $html]) {
                if (! Page::withTrashed()->where('slug', $slug)->exists()) {
                    Page::create([
                        'title' => $title, 'slug' => $slug, 'show_title' => true,
                        'blocks' => [['type' => 'rich_text', 'data' => ['content' => $html]]],
                        'status' => ContentStatus::Published, 'published_at' => now(),
                    ]);
                }
            }
        });

        $this->updateMenu('header', function (array $items) {
            if (collect($items)->contains(fn ($i) => ($i['url'] ?? null) === '/courses')) {
                return $items;
            }
            $entry = ['label' => 'Courses', 'url' => '/courses', 'children' => [
                ['label' => 'Online courses', 'url' => '/courses', 'description' => 'Self-paced programmes with video lessons'],
                ['label' => 'Live webinars', 'url' => '/webinars', 'description' => 'Interactive sessions with Q&A and recordings'],
                ['label' => 'Membership', 'url' => '/membership', 'description' => 'Unlock every course, webinar and members-only case'],
            ]];
            $about = collect($items)->search(fn ($i) => ($i['url'] ?? null) === '/about');
            array_splice($items, $about === false ? count($items) : $about, 0, [$entry]);

            return $items;
        });

        $this->updateMenu('footer_tertiary', function (array $items) {
            $items = array_values(array_filter($items, fn ($i) => ($i['label'] ?? '') !== 'Courses (coming soon)'));
            foreach (['/courses' => 'Courses', '/webinars' => 'Webinars', '/membership' => 'Membership'] as $url => $label) {
                if (! collect($items)->contains(fn ($i) => ($i['url'] ?? null) === $url)) {
                    $items[] = ['label' => $label, 'url' => $url];
                }
            }

            return $items;
        });

        $this->updateMenu('footer_legal', function (array $items) {
            foreach (['/terms-of-sale' => 'Terms of sale', '/refund-policy' => 'Refund policy'] as $url => $label) {
                if (! collect($items)->contains(fn ($i) => ($i['url'] ?? null) === $url)) {
                    $items[] = ['label' => $label, 'url' => $url];
                }
            }

            return $items;
        });
    }

    protected function updateMenu(string $location, callable $change): void
    {
        $menu = Menu::where('location', $location)->first();
        if ($menu) {
            $menu->forceFill(['items' => $change($menu->items ?? [])])->save();
        }
    }

    public function down(): void
    {
        // Content changes are intentionally kept.
    }
};
