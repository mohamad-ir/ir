<?php

namespace App\Filament\Pages\Insights;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Widgets\Analytics\ChannelsChart;
use App\Filament\Widgets\Analytics\SubscribersChart;
use App\Filament\Widgets\Analytics\TrafficChart;
use App\Services\Analytics\AnalyticsQuery;
use App\Services\Analytics\GeoLocator;
use App\Support\Csv;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;

class Analytics extends Page
{
    use TranslatesPageLabels;

    protected static ?string $slug = 'insights/analytics';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Insights';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Analytics';

    protected string $view = 'filament.pages.analytics';

    protected Width|string|null $maxContentWidth = Width::Full;

    #[Url]
    public int $days = 30;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('analytics.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return __('First-party, cookieless statistics. :count visitors in the last 30 minutes.', ['count' => number_format(AnalyticsQuery::for(1)->realtime())]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label(__('Export CSV'))->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => response()->streamDownload(function () {
                    $q = AnalyticsQuery::for($this->days);
                    $out = fopen('php://output', 'w');
                    Csv::put($out, ['date', 'page_views', 'visits']);
                    $s = $q->timeseries();
                    foreach ($s['labels'] as $i => $label) {
                        Csv::put($out, [$label, $s['views'][$i], $s['visits'][$i]]);
                    }
                    Csv::put($out, []);
                    Csv::put($out, ['page', 'path', 'views', 'visits']);
                    foreach ($q->topPages(50) as $p) {
                        Csv::put($out, [$p->title, $p->path, $p->views, $p->visits]);
                    }
                    fclose($out);
                }, "analytics-last-{$this->days}-days.csv")),
        ];
    }

    public function getViewData(): array
    {
        $q = AnalyticsQuery::for($this->days);

        return [
            'totals' => $q->totals(),
            'pages' => $q->topPages(10),
            'cases' => $q->topCases(8),
            'videos' => $q->videoEngagement(6),
            'referrers' => $q->referrers(8),
            'campaigns' => $q->campaigns(6),
            'searches' => $q->searches(8),
            'shares' => $q->shares(),
            'countries' => $q->breakdown('country', 8),
            'geoAvailable' => GeoLocator::available() || request()->headers->has('CF-IPCountry'),
            'widgets' => [TrafficChart::class, ChannelsChart::class, SubscribersChart::class],
        ];
    }
}
