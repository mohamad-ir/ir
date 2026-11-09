<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Forms\MediaPicker;
use App\Models\Page;
use App\Support\Social;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class GeneralSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/site';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 1;

    protected function groups(): array
    {
        return ['site', 'doctor', 'social', 'security', 'admin'];
    }

    protected function permission(): string
    {
        return 'settings.manage';
    }

    protected function components(): array
    {
        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('Website')->label(__('Website'))->icon('heroicon-o-globe-alt')->schema([
                    Section::make()->columns(2)->schema([
                        \Filament\Forms\Components\Placeholder::make('identity')->hiddenLabel()->columnSpanFull()
                            ->content(fn () => new \Illuminate\Support\HtmlString('<span class="text-sm text-gray-500">The site name, tagline, logos and icons are edited in <a class="font-medium text-primary-600 underline" href="'.e(ThemeSettings::getUrl(['tab' => 'site-identity::data::tab'])).'">Design › Theme &amp; branding › Site identity</a>.</span>')),
                        Textarea::make('site.description')->label(__('Website description'))->rows(2)->maxLength(300)->columnSpanFull()
                            ->helperText(__('Used on the homepage in Google results and when the site is shared.')),
                        Select::make('site.homepage_id')->label(__('Homepage'))->options(fn () => Page::orderBy('title')->pluck('title', 'id'))->searchable(),
                        Select::make('site.timezone')->label(__('Time zone'))->options(fn () => collect(timezone_identifiers_list())->mapWithKeys(fn ($t) => [$t => $t]))->searchable(),
                        TextInput::make('site.contact_email')->label(__('Public contact email'))->email(),
                        TextInput::make('site.notification_email')->label(__('Send enquiry notifications to'))->email(),
                        Toggle::make('site.registration_enabled')->label(__('Allow visitors to create accounts')),
                        Toggle::make('site.cookie_banner')->label(__('Show cookie consent banner when Google Analytics is enabled')),
                        Textarea::make('site.medical_disclaimer')->label(__('Medical disclaimer'))->rows(3)->columnSpanFull()
                            ->helperText(__('Shown in the footer of every page and every email.')),
                    ]),
                ]),
                Tab::make('Dashboard')->label(__('Dashboard'))->icon('heroicon-o-language')->schema([
                    Section::make(__('Dashboard language'))->description(__('Each team member chooses English or فارسی for the dashboard in their profile (or from the user menu). The website, member pages and emails always stay in English.'))->schema([
                        \Filament\Forms\Components\ToggleButtons::make('admin.fa_direction')->label(__('Layout direction when the dashboard is in Persian'))->inline()
                            ->options(['ltr' => __('Left to right'), 'rtl' => __('Right to left')])
                            ->helperText(__('Left to right keeps the same layout as English. Right to left mirrors the dashboard as usual for Persian.')),
                    ]),
                ]),
                Tab::make('Doctor profile')->label(__('Doctor profile'))->icon('heroicon-o-user-circle')->schema([
                    Section::make()->description(__('Used by the profile section of the page builder, article bylines and Google’s “Physician” structured data.'))->columns(2)->schema([
                        TextInput::make('doctor.name')->label(__('Full name'))->required(),
                        TextInput::make('doctor.credentials')->label(__('Post-nominals'))->placeholder(__('MD, FRCOphth')),
                        TextInput::make('doctor.role')->label(__('Role / specialty'))->columnSpanFull(),
                        Textarea::make('doctor.short_bio')->label(__('Short biography'))->rows(3)->columnSpanFull(),
                        MediaPicker::make('doctor.photo_id')->label(__('Professional photo'))->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Social & professional profiles')->label(__('Social & professional profiles'))->icon('heroicon-o-share')->schema([
                    Section::make()->description(__('Links appear in the footer, the profile section and in structured data (sameAs).'))->columns(2)->schema(
                        collect(Social::NETWORKS)->map(fn ($label, $key) => TextInput::make('social.'.$key)->label($label)->url()->placeholder('https://'))->values()->all()
                    ),
                ]),
                Tab::make('Security')->label(__('Security'))->icon('heroicon-o-lock-closed')->schema([
                    Section::make('Dashboard sign-in')->heading(__('Dashboard sign-in'))->schema([
                        Toggle::make('security.require_staff_mfa')->label(__('Require two-factor authentication for all team members'))
                            ->helperText(__('Strongly recommended. Team members will be asked to set up an authenticator app at their next sign-in.')),
                    ]),
                    Section::make('Spam protection (Cloudflare Turnstile)')->heading(__('Spam protection (Cloudflare Turnstile)'))->description(__('Optional invisible challenge on the contact form. Get free keys at dash.cloudflare.com.'))->columns(2)->schema([
                        TextInput::make('security.turnstile_site_key')->label(__('Site key')),
                        TextInput::make('security.turnstile_secret')->label(__('Secret key'))->password()->revealable()
                            ->placeholder(fn ($get) => $get('security.turnstile_secret_set') ? __('•••••••• (saved — leave empty to keep)') : null),
                    ]),
                ]),
            ]),
        ];
    }
}
