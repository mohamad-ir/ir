<?php

use App\Models\Banner;
use App\Models\BannerSlide;

beforeEach(fn () => $this->seedRoles());

function banner(string $placement, array $slide = []): BannerSlide
{
    $banner = Banner::create(['name' => 'Test', 'placement' => $placement]);

    return $banner->slides()->create(['media_id' => fakeImage()->id, 'title' => 'Spring course launch', 'link_url' => '/courses', ...$slide]);
}

it('shows a slider on its placement only', function () {
    banner('cases');
    $this->get('/cases')->assertSee('Spring course launch')->assertSee('aria-roledescription="carousel"', false);
    $this->get('/articles')->assertDontSee('Spring course launch');
});

it('honours the slide schedule and switches', function () {
    banner('articles', ['title' => 'Future slide', 'starts_at' => now()->addDay()]);
    $this->get('/articles')->assertDontSee('Future slide');

    $slide = banner('videos', ['title' => 'Old slide', 'ends_at' => now()->subHour()]);
    $this->get('/videos')->assertDontSee('Old slide');
    $slide->update(['ends_at' => null, 'is_active' => false]);
    $this->get('/videos')->assertDontSee('Old slide');
});

it('opens external links safely in a new tab', function () {
    $slide = banner('courses', ['link_url' => 'https://example.org/meeting', 'new_tab' => true, 'button_label' => 'Book a place']);
    expect($slide->isExternal())->toBeTrue();
    $this->get('/courses')->assertSee('href="https://example.org/meeting"', false)->assertSee('rel="noopener', false);
    expect((new BannerSlide(['link_url' => 'courses/x']))->href())->toBe('/courses/x');
});

it('counts clicks', function () {
    $slide = banner('home');
    $this->post("/banner-click/{$slide->id}")->assertNoContent();
    expect($slide->fresh()->clicks_count)->toBe(1);
});

it('lets designers manage banners in the dashboard', function () {
    banner('home');
    $this->actingAsStaff('administrator');
    $this->get('/admin/banners')->assertOk()->assertSee('Test');
    $this->actingAsStaff('author');
    $this->get('/admin/banners')->assertForbidden();
});
