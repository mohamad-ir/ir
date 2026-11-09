<?php

use App\Filament\Pages\Settings\ThemeSettings;
use App\Services\Branding\BrandingService;
use App\Services\Media\MediaService;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seedRoles();
    Storage::fake('public');
});

function brandImage(int $w, int $h, string $name = 'img.png'): \App\Models\Media
{
    return app(MediaService::class)->store(UploadedFile::fake()->image($name, $w, $h));
}

it('serves default icons and app names before anything is uploaded', function () {
    $this->get('/')->assertOk()
        ->assertSee('favicon.svg', false)
        ->assertSee('/icons/favicon-32.png', false)
        ->assertSee('/icons/apple-touch-icon.png', false)
        ->assertSee('<meta name="apple-mobile-web-app-title" content="OCT Academy">', false);
    $this->get('/favicon.ico')->assertRedirect(asset('icons/favicon-48.png'));
    foreach (array_keys(BrandingService::ICONS) as $name) {
        expect(public_path("icons/{$name}.png"))->toBeFile();
    }
});

it('generates the icon set from the square site icon and replaces it when the icon changes', function () {
    $this->actingAsStaff();
    $icon = brandImage(600, 600, 'icon.png');

    Livewire::test(ThemeSettings::class)->set('data.branding.favicon_id', $icon->id)->set('data.branding.short_name', 'OCT Acad')->call('save')->assertHasNoErrors();

    $set = Settings::get('branding.icon_set');
    expect($set['files'])->toHaveKeys(array_keys(BrandingService::ICONS));
    foreach (BrandingService::ICONS as $name => [$size]) {
        expect(getimagesize(Storage::disk('public')->path($set['files'][$name])))->toMatchArray([0 => $size, 1 => $size, 'mime' => 'image/png']);
    }
    $this->get('/')->assertSee(Storage::disk('public')->url($set['files']['apple-touch-icon']), false)->assertSee('content="OCT Acad"', false)->assertDontSee('favicon.svg', false);
    expect(app(BrandingService::class)->manifestIcons())->toHaveCount(3)->and(app(BrandingService::class)->manifestIcons()[2]['purpose'])->toBe('maskable');

    $old = $set['dir'];
    Livewire::test(ThemeSettings::class)->set('data.branding.favicon_id', brandImage(512, 512, 'new.png')->id)->call('save')->assertHasNoErrors();
    expect(Storage::disk('public')->directoryExists($old))->toBeFalse();

    Livewire::test(ThemeSettings::class)->set('data.branding.favicon_id', null)->call('save');
    expect(Settings::get('branding.icon_set'))->toBeNull();
});

it('rejects a site icon that is not square or too small', function (int $w, int $h) {
    $this->actingAsStaff();
    Livewire::test(ThemeSettings::class)->set('data.branding.favicon_id', brandImage($w, $h)->id)->call('save')->assertHasErrors('data.branding.favicon_id');
})->with([[640, 400], [256, 256]]);

it('uses the right logo for light and dark backgrounds with the chosen alt text', function () {
    $light = brandImage(480, 120, 'light.png');
    Settings::setMany(['branding.logo_id' => $light->id, 'branding.logo_alt' => 'OCT Academy logo']);

    // No dark version yet: the main logo becomes a white silhouette in dark mode
    $this->get('/')->assertSee($light->url(), false)->assertSee('alt="OCT Academy logo"', false)->assertSee('dark:brightness-0 dark:invert', false);

    $dark = brandImage(480, 120, 'dark.png');
    Settings::set('branding.logo_dark_id', $dark->id);
    $this->get('/')->assertSee($dark->url(), false)->assertSee('hidden dark:block', false);
    $this->get('/login')->assertSee($dark->url(), false); // sign-in panel sits on a dark background
});

it('edits the site name in Theme & branding without touching other site settings', function () {
    $this->actingAsStaff();
    Settings::set('site.contact_email', 'keep@example.org');
    Livewire::test(ThemeSettings::class)->set('data.site.name', 'Retina School')->set('data.site.tagline', 'Learn OCT')->call('save')->assertHasNoErrors();

    expect(Settings::get('site.name'))->toBe('Retina School')->and(Settings::get('site.contact_email'))->toBe('keep@example.org');
    $this->get('/')->assertSee('<title>', false)->assertSee('Retina School');
});

it('adds the organisation logo to structured data', function () {
    $this->get('/articles/'.\App\Models\Article::factory()->create()->slug)->assertSee('"logo":{"@type":"ImageObject"', false);
});

it('shows the developer credit and the back-to-top button on public pages', function () {
    $this->get('/')->assertSee('Developed by <span class="font-medium', false)->assertSee('mj tayaran')
        ->assertSee('aria-label="Back to top"', false);
});
