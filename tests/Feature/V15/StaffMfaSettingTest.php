<?php

use App\Http\Middleware\RequireStaffMfa;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(fn () => $this->seedRoles());

it('applies “require two-factor authentication” on every request, also with cached routes', function () {
    $staff = $this->actingAsStaff();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::auth()->login($staff);
    // In tests Filament does not register its 2FA routes; stand in for the set-up page.
    Route::get('/admin/multi-factor-authentication/set-up', fn () => 'setup')->name('filament.admin.auth.multi-factor-authentication.set-up-required');
    app('router')->getRoutes()->refreshNameLookups();

    $through = fn () => (new RequireStaffMfa)->handle(Request::create('/admin'), fn () => response('dashboard'));

    Settings::set('security.require_staff_mfa', true);
    $response = $through();
    expect($response->isRedirect())->toBeTrue()
        ->and($response->headers->get('Location'))->toContain('multi-factor-authentication');

    Settings::set('security.require_staff_mfa', false);
    expect($through()->getContent())->toBe('dashboard');
});

it('registers the two-factor check on dashboard routes independently of the current setting', function () {
    $panel = Filament::getPanel('admin');
    expect($panel->getMultiFactorAuthenticationRequiredMiddleware())->toBe(RequireStaffMfa::class);
});
