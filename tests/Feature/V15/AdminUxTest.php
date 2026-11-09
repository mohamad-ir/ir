<?php

use App\Filament\Resources\Pages\PageResource;
use App\Models\Article;
use App\Models\Page;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;

beforeEach(fn () => $this->seedRoles());

it('shows an English | فارسی switch in the dashboard top bar that remembers the choice', function () {
    $admin = $this->actingAsStaff();

    $this->get('/admin')->assertOk()
        ->assertSee('oa-locale-switch', false)
        ->assertSee(route('admin.locale', ['locale' => 'fa']), false)
        ->assertSee('aria-current="true"', false);

    $this->get('/admin/locale/fa', ['Referer' => url('/admin/pages')])->assertRedirect(url('/admin/pages'));
    expect($admin->fresh()->admin_locale)->toBe('fa');

    $this->get('/admin')->assertSee('lang="en"', false)->assertSee('داشبورد');
    $this->get('/')->assertOk()->assertSee('<html lang="en"', false)->assertDontSee('oa-locale-switch', false);
});

it('keeps the open/closed state of builder and repeater items when items are added', function () {
    expect(PageResource::builder()->shouldPersistCollapsed())->toBeTrue()
        ->and(Builder::make('x')->shouldPersistCollapsed())->toBeTrue()
        ->and(Repeater::make('y')->shouldPersistCollapsed())->toBeTrue();

    $this->actingAsStaff();
    // The stored states are cleared on every page load, so each visit starts from the default.
    $this->get('/admin')->assertSee('_x_filament:(builder|repeater):collapsed:', false);
});

it('keeps previews left-to-right and in view in every editor with a live preview', function () {
    $css = file_get_contents(resource_path('css/filament/admin/theme.css'));
    expect($css)->toMatch('/\.oa-preview-frame-wrap \{ direction: ltr;/')
        ->toContain('.oa-sticky-preview { position: sticky;')
        ->toContain('[dir="rtl"] :is(input.fi-input, textarea');

    $this->actingAsStaff();
    $page = Page::create(['title' => 'UX page', 'slug' => 'ux-page', 'status' => 'draft', 'blocks' => []]);
    foreach (['/admin/pages/'.$page->id.'/edit', '/admin/pages/create', '/admin/reusable-blocks/create', '/admin/settings/theme', '/admin/popups/create'] as $url) {
        $this->get($url)->assertOk()->assertSee('oa-sticky-preview', false)->assertSee('togglePreview()', false);
    }
});

it('isolates dates in the content editor heading and translates dashboard texts', function () {
    $this->actingAsStaff();
    $article = Article::factory()->create(['published_at' => now()->subDay(), 'status' => 'published']);

    $this->get('/admin/articles/'.$article->id.'/edit')->assertOk()->assertSee('<bdi>', false)->assertSee('History (', false);

    app()->setLocale('fa');
    expect(__('History (:count)', ['count' => 3]))->toBe('تاریخچه (3)')
        ->and(__('filament-forms::components.builder.actions.clone.label'))->toBe('کپی')
        ->and(__('filament-forms::components.repeater.actions.clone.label'))->toBe('کپی')
        ->and(__('filament-forms::components.builder.actions.delete.label'))->toBe('حذف')
        ->and(__('At least 10 characters with letters and numbers.'))->not->toBe('At least 10 characters with letters and numbers.')
        ->and(__('Career stages'))->toBe('مراحل حرفه‌ای');
});
