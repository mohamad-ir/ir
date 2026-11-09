<?php

use App\Models\User;
use App\Services\Theme\ThemeService;
use App\Support\Settings;

beforeEach(fn () => $this->seedRoles());

it('follows the device by default and renders the dark palette', function () {
    $this->get('/')->assertOk()
        ->assertSee('data-color-mode="system"', false)
        ->assertSee(':root.dark{', false)
        ->assertSee('prefers-color-scheme: dark', false);
});

it('renders the remembered choice on the server so the page never flashes', function () {
    $this->withUnencryptedCookie('color_mode', 'dark')->get('/')->assertSee('class="scroll-smooth dark"', false)->assertSee('data-color-mode="dark"', false);
    $this->withUnencryptedCookie('color_mode', 'nonsense')->get('/')->assertSee('data-color-mode="system"', false);
});

it('saves the choice in a cookie and on the member account', function () {
    $this->postJson('/color-mode', ['mode' => 'dark'])->assertOk()->assertPlainCookie('color_mode', 'dark');
    $this->postJson('/color-mode', ['mode' => 'purple'])->assertStatus(422);

    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/color-mode', ['mode' => 'light'])->assertOk();
    expect($user->fresh()->color_mode)->toBe('light');

    // The account wins over a cookie left on another device.
    $this->withUnencryptedCookie('color_mode', 'dark')->get('/')->assertSee('data-color-mode="light"', false);
});

it('applies the site default when visitors may not choose', function () {
    Settings::set('theme.color_mode', 'dark');
    Settings::set('theme.color_mode_switch', false);

    $this->withUnencryptedCookie('color_mode', 'light')->get('/')->assertSee('data-color-mode="dark"', false)
        ->assertDontSee('Light, dark or system display');
});

it('keeps readable contrast in dark mode for every colour preset', function () {
    $theme = app(ThemeService::class);
    foreach (ThemeService::PRESETS as $name => $preset) {
        $dark = $theme->darkVars($preset);
        expect($theme->contrastRatio($dark['--color-fg'], $dark['--color-paper']))->toBeGreaterThanOrEqual(7, $name)
            ->and($theme->contrastRatio($dark['--color-accent-ink'], $dark['--color-paper']))->toBeGreaterThanOrEqual(4.5, $name)
            ->and($theme->contrastRatio($dark['--color-ink'], $dark['--color-paper']))->toBeGreaterThanOrEqual(7, $name)
            ->and($theme->contrastRatio($dark['--color-panel-contrast'], $dark['--color-panel']))->toBeGreaterThanOrEqual(4.5, $name);
    }
});

it('uses the owner’s dark colours when set and warns about poor contrast', function () {
    $theme = app(ThemeService::class);
    expect($theme->darkVars(['dark_background' => '#101010'])['--color-paper'])->toBe('#101010')
        ->and(collect($theme->audit([...Settings::group('theme'), 'dark_background' => '#777777', 'dark_text' => '#888888']))
            ->contains(fn ($w) => str_contains($w, 'Dark mode')))->toBeTrue();
});

it('offers the dark mode settings and a dark preview in the dashboard', function () {
    $this->actingAsStaff();
    $this->get('/admin/settings/theme')->assertOk()->assertSee('Light &amp; dark mode', false);
    $this->get('/?theme_preview=abc&color_mode=dark')->assertSee('data-color-mode="dark"', false);
});
