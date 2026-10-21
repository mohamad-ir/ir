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
        return ['site', 'doctor', 'social', 'security'];
    }

    protected function permission(): string
    {
        return 'settings.manage';
    }

    protected function components(): array
    {
        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('Website')->icon('heroicon-o-globe-alt')->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('site.name')->label('Website name')->required()->maxLength(80),
                        TextInput::make('site.tagline')->label('Tagline')->maxLength(140),
                        Textarea::make('site.description')->label('Website description')->rows(2)->maxLength(300)->columnSpanFull()
                            ->helperText('Used on the homepage in Google results and when the site is shared.'),
                        Select::make('site.homepage_id')->label('Homepage')->options(fn () => Page::orderBy('title')->pluck('title', 'id'))->searchable(),
                        Select::make('site.timezone')->label('Time zone')->options(fn () => collect(timezone_identifiers_list())->mapWithKeys(fn ($t) => [$t => $t]))->searchable(),
                        TextInput::make('site.contact_email')->label('Public contact email')->email(),
                        TextInput::make('site.notification_email')->label('Send enquiry notifications to')->email(),
                        Toggle::make('site.registration_enabled')->label('Allow visitors to create accounts'),
                        Toggle::make('site.cookie_banner')->label('Show cookie consent banner when Google Analytics is enabled'),
                        Textarea::make('site.medical_disclaimer')->label('Medical disclaimer')->rows(3)->columnSpanFull()
                            ->helperText('Shown in the footer of every page and every email.'),
                    ]),
                ]),
                Tab::make('Doctor profile')->icon('heroicon-o-user-circle')->schema([
                    Section::make()->description('Used by the profile section of the page builder, article bylines and Google’s “Physician” structured data.')->columns(2)->schema([
                        TextInput::make('doctor.name')->label('Full name')->required(),
                        TextInput::make('doctor.credentials')->label('Post-nominals')->placeholder('MD, FRCOphth'),
                        TextInput::make('doctor.role')->label('Role / specialty')->columnSpanFull(),
                        Textarea::make('doctor.short_bio')->label('Short biography')->rows(3)->columnSpanFull(),
                        MediaPicker::make('doctor.photo_id')->label('Professional photo')->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Social & professional profiles')->icon('heroicon-o-share')->schema([
                    Section::make()->description('Links appear in the footer, the profile section and in structured data (sameAs).')->columns(2)->schema(
                        collect(Social::NETWORKS)->map(fn ($label, $key) => TextInput::make('social.'.$key)->label($label)->url()->placeholder('https://'))->values()->all()
                    ),
                ]),
                Tab::make('Security')->icon('heroicon-o-lock-closed')->schema([
                    Section::make('Dashboard sign-in')->schema([
                        Toggle::make('security.require_staff_mfa')->label('Require two-factor authentication for all team members')
                            ->helperText('Strongly recommended. Team members will be asked to set up an authenticator app at their next sign-in.'),
                    ]),
                    Section::make('Spam protection (Cloudflare Turnstile)')->description('Optional invisible challenge on the contact form. Get free keys at dash.cloudflare.com.')->columns(2)->schema([
                        TextInput::make('security.turnstile_site_key')->label('Site key'),
                        TextInput::make('security.turnstile_secret')->label('Secret key')->password()->revealable()
                            ->placeholder(fn ($get) => $get('security.turnstile_secret_set') ? '•••••••• (saved — leave empty to keep)' : null),
                    ]),
                ]),
            ]),
        ];
    }
}
