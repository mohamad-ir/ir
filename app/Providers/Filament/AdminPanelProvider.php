<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\Media;
use App\Support\Settings;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
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
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: fn () => (bool) Settings::get('security.require_staff_mfa', true) && ! app()->runningUnitTests(),
            )
            ->brandName(fn () => Settings::get('site.name'))
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2rem')
            ->favicon(fn () => Media::find(Settings::get('branding.favicon_id'))?->url() ?? asset('favicon.svg'))
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
            ->defaultAvatarProvider(\App\Filament\Support\InitialsAvatarProvider::class)
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->unsavedChangesAlerts()
            ->maxContentWidth(Width::Full)
            ->globalSearchKeyBindings(['mod+k'])
            ->navigationGroups([
                NavigationGroup::make('Content'),
                NavigationGroup::make('Exam preparation'),
                NavigationGroup::make('Learning'),
                NavigationGroup::make('Library'),
                NavigationGroup::make('Design'),
                NavigationGroup::make('Audience'),
                NavigationGroup::make('Sales'),
                NavigationGroup::make('Insights'),
                NavigationGroup::make('SEO'),
                NavigationGroup::make('System')->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('View website')
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
