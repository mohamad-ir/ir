<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Middleware\AuthenticateDashboard;
use App\Http\Middleware\RequireStaffMfa;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAdminLocale;
use App\Services\Branding\BrandingService;
use App\Support\Settings;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('cms.admin_path'))
            ->login()
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                // Always registered (also in cached routes); RequireStaffMfa applies the setting on every request.
                isRequired: fn () => ! app()->runningUnitTests(),
            )
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireStaffMfa::class)
            ->brandName(fn () => Settings::get('site.name'))
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2rem')
            ->favicon(fn () => app(BrandingService::class)->iconUrl('favicon-32'))
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => app(BrandingService::class)->headTags())
            ->colors([
                'primary' => [50 => 'oklch(0.975 0.012 196)', 100 => 'oklch(0.948 0.024 196)', 200 => 'oklch(0.9 0.045 196)', 300 => 'oklch(0.83 0.07 196)', 400 => 'oklch(0.72 0.09 196)', 500 => 'oklch(0.6 0.098 196)', 600 => 'oklch(0.52 0.092 196)', 700 => 'oklch(0.45 0.082 196)', 800 => 'oklch(0.39 0.07 196)', 900 => 'oklch(0.33 0.058 196)', 950 => 'oklch(0.24 0.042 196)'],
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->font('Inter Variable', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->unsavedChangesAlerts()
            ->maxContentWidth(Width::Full)
            ->globalSearchKeyBindings(['mod+k'])
            // Resources name their group in English (the key); the label is translated for the dashboard language.
            ->navigationGroups([
                'Content' => NavigationGroup::make()->label(fn () => __('Content')),
                'Exam preparation' => NavigationGroup::make()->label(fn () => __('Exam preparation')),
                'Learning' => NavigationGroup::make()->label(fn () => __('Learning')),
                'Library' => NavigationGroup::make()->label(fn () => __('Library')),
                'Design' => NavigationGroup::make()->label(fn () => __('Design')),
                'Audience' => NavigationGroup::make()->label(fn () => __('Audience')),
                'Sales' => NavigationGroup::make()->label(fn () => __('Sales')),
                'Insights' => NavigationGroup::make()->label(fn () => __('Insights')),
                'SEO' => NavigationGroup::make()->label(fn () => __('SEO')),
                'System' => NavigationGroup::make()->label(fn () => __('System'))->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('View website')->label(fn () => __('View website'))
                    ->url(fn () => url('/'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->sort(1000),
            ])
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => Blade::render('<x-filament.quick-create />'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                // Same security headers as the website (X-Frame-Options against clickjacking of the sign-in page, nosniff, HSTS…).
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->middleware([SetAdminLocale::class], isPersistent: true)
            // Dashboard language: always visible English | فارسی switch in the top bar (the public site stays English).
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.locale-switch'))
            // Builder/repeater items keep their open/closed state when an item is added, cloned or moved
            // (Filament otherwise re-opens every item). The stored states are cleared on each page load.
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString(
                "<script>(function(){function c(){try{Object.keys(localStorage).forEach(function(k){if(/^(_x_)?filament:(builder|repeater):collapsed:/.test(k))localStorage.removeItem(k)})}catch(e){}}c();document.addEventListener('livewire:navigating',c)})()</script>"
            ))
            ->authMiddleware([
                // Filament's Authenticate, plus a clear explanation instead of a bare 403 for signed-in users without dashboard access.
                AuthenticateDashboard::class,
            ], isPersistent: true); // also on Livewire updates: losing dashboard access or deactivation applies to open pages at once
    }

    public function boot(): void
    {
        Builder::configureUsing(fn (Builder $builder) => $builder->persistCollapsed());
        Repeater::configureUsing(fn (Repeater $repeater) => $repeater->persistCollapsed());
    }
}
