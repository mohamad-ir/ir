<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Settings\AnalyticsSettings;
use App\Filament\Pages\Settings\EmailSettings;
use App\Filament\Pages\Settings\GeneralSettings;
use App\Filament\Pages\Settings\ThemeSettings;
use App\Filament\Pages\System\SystemTools;
use App\Services\Demo\DemoContent;
use App\Support\Settings;
use Filament\Widgets\Widget;

/** Guides a new owner through launching the site; hides itself once complete. */
class SetupChecklist extends Widget
{
    protected string $view = 'filament.widgets.setup-checklist';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    public static function canView(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function items(): array
    {
        $cron = Settings::get('system.last_cron_at');

        return [
            ['Add your name, credentials and professional photo', Settings::get('doctor.name') !== 'Dr Alex Morgan' && filled(Settings::get('doctor.photo_id')) && ! in_array('media:'.Settings::get('doctor.photo_id'), DemoContent::items(), true), GeneralSettings::getUrl()],
            ['Upload your logo and favicon (optional)', filled(Settings::get('branding.logo_id')) || filled(Settings::get('branding.favicon_id')), ThemeSettings::getUrl()],
            ['Connect an email mailbox for notifications and the newsletter', filled(Settings::get('mail.mailer')) || config('mail.default') === 'smtp', EmailSettings::getUrl()],
            ['Add the cron job so newsletters and backups run automatically', $cron && now()->diffInMinutes(\Illuminate\Support\Carbon::parse($cron)) < 15, SystemTools::getUrl()],
            ['Turn on two-factor authentication for your account', filled(auth()->user()?->app_authentication_secret), filament()->getProfileUrl()],
            ['Connect Google Analytics & Search Console (optional)', filled(Settings::get('analytics.google_credentials')) || filled(Settings::get('analytics.ga4_measurement_id')), AnalyticsSettings::getUrl()],
            ['Replace the sample cases and remove sample content', ! DemoContent::present(), SystemTools::getUrl()],
        ];
    }

    public function getViewData(): array
    {
        $items = $this->items();

        return ['items' => $items, 'done' => collect($items)->where(1, true)->count()];
    }
}
