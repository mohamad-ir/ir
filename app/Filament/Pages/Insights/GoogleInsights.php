<?php

namespace App\Filament\Pages\Insights;

use App\Filament\Pages\Settings\AnalyticsSettings;
use App\Services\Google\GoogleClient;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Throwable;

class GoogleInsights extends Page
{
    protected static ?string $slug = 'insights/google';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Insights';

    protected static ?string $navigationLabel = 'Google Analytics & Search';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Google Analytics & Search Console';

    protected string $view = 'filament.pages.google';

    #[Url]
    public int $days = 28;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('analytics.view') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')->label('Refresh data')->icon('heroicon-o-arrow-path')->color('gray')
                ->action(fn () => GoogleClient::clearCache()),
            Action::make('settings')->label('Connection settings')->icon('heroicon-o-cog-6-tooth')->color('gray')
                ->url(AnalyticsSettings::getUrl())->visible(fn () => auth()->user()->can('settings.manage')),
        ];
    }

    public function getViewData(): array
    {
        $google = app(GoogleClient::class);
        $data = ['ga4' => null, 'gsc' => null, 'ga4Error' => null, 'gscError' => null, 'ga4Configured' => $google->ga4Configured(), 'gscConfigured' => $google->gscConfigured()];
        $start = $this->days.'daysAgo';

        if ($google->ga4Configured()) {
            try {
                $data['ga4'] = [
                    'totals' => $google->ga4Report([], ['activeUsers', 'sessions', 'screenPageViews', 'averageSessionDuration', 'engagementRate'], $start)[0] ?? [],
                    'pages' => $google->ga4Report(['pagePath', 'pageTitle'], ['screenPageViews', 'activeUsers'], $start, 'today', 10, ['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]),
                    'sources' => $google->ga4Report(['sessionDefaultChannelGroup'], ['sessions'], $start, 'today', 8, ['metric' => ['metricName' => 'sessions'], 'desc' => true]),
                    'countries' => $google->ga4Report(['country'], ['activeUsers'], $start, 'today', 8, ['metric' => ['metricName' => 'activeUsers'], 'desc' => true]),
                ];
            } catch (Throwable $e) {
                $data['ga4Error'] = $e->getMessage();
            }
        }

        if ($google->gscConfigured()) {
            try {
                $data['gsc'] = [
                    'queries' => $google->searchAnalytics(['query'], $this->days, 15),
                    'pages' => $google->searchAnalytics(['page'], $this->days, 10),
                    'daily' => $google->searchAnalytics(['date'], $this->days, 100),
                ];
            } catch (Throwable $e) {
                $data['gscError'] = $e->getMessage();
            }
        }

        return $data;
    }
}
