<?php

use App\Enums\AccessLevel;
use App\Filament\Resources\Galleries\Pages\CreateGallery;
use App\Models\Gallery;
use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

it('lists galleries and shows an album with the viewer and ImageGallery data', function () {
    $gallery = Gallery::factory()->withImages(3)->create(['title' => 'OCT atlas']);
    Gallery::factory()->create(['title' => 'Draft album', 'status' => 'draft']);

    $this->get('/galleries')->assertOk()->assertSee('OCT atlas')->assertDontSee('Draft album');
    $this->get($gallery->urlPath())->assertOk()->assertSee('galleryViewer', false)->assertSee('Caption 2')
        ->assertSee('"@type":"ImageGallery"', false)->assertSee('photo-'.$gallery->id.'-3.jpg', false);
});

it('hides members-only galleries from other visitors', function () {
    $gallery = Gallery::factory()->withImages(2)->create(['access_level' => AccessLevel::Members]);

    $this->get($gallery->urlPath())->assertOk()->assertSee('included with membership')->assertDontSee('photo-'.$gallery->id.'-2.jpg', false);
    $this->actingAs(makeMember())->get($gallery->urlPath())->assertSee('photo-'.$gallery->id.'-2.jpg', false);
});

it('adds the galleries link under OCT cases and includes albums in the sitemap and search', function () {
    $this->seed(DatabaseSeeder::class);
    Gallery::factory()->withImages(1)->create(['title' => 'Uveitis fundus atlas']);

    $header = \App\Models\Menu::where('location', 'header')->first()->items;
    expect(collect(collect($header)->firstWhere('url', '/cases')['children'])->pluck('url')->all())->toContain('/galleries');
    $this->get('/sitemap-galleries.xml')->assertOk()->assertSee('image:image', false);
    $this->get('/search?q=uveitis+fundus+atlas')->assertOk()->assertSee('Uveitis fundus atlas');
});

it('shows an album inside a page through the gallery block', function () {
    $this->seed(DatabaseSeeder::class);
    $gallery = Gallery::factory()->withImages(4)->create();
    $html = (string) app(BlockRegistry::class)->render([['type' => 'gallery', 'data' => ['source' => 'album', 'gallery_id' => $gallery->id, 'limit' => 2]]]);

    expect($html)->toContain('galleryViewer')->toContain('photo-'.$gallery->id.'-2.jpg')->not->toContain('photo-'.$gallery->id.'-3.jpg');
});

it('creates a gallery from the dashboard', function () {
    $this->actingAsStaff();

    Livewire::test(CreateGallery::class)->fillForm(['title' => 'Conference 2026', 'slug' => 'conference-2026', 'layout' => 'masonry', 'status' => 'published'])
        ->call('create')->assertHasNoFormErrors();

    expect(Gallery::where('slug', 'conference-2026')->value('layout'))->toBe('masonry');
    $this->get('/'.config('cms.admin_path').'/galleries/'.Gallery::value('id').'/edit')->assertOk();
});
