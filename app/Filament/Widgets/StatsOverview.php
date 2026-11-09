<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\PageView;
use App\Models\Question;
use App\Models\Subscriber;
use App\Models\Video;
use App\Services\Analytics\AnalyticsQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $q = AnalyticsQuery::for(30);
        $totals = $q->totals();
        $series = $q->timeseries();
        $trend = fn (?float $c) => $c === null ? null : __(':change% vs previous 30 days', ['change' => ($c >= 0 ? '+' : '').$c]);

        $stats = [];
        if (auth()->user()->can('analytics.view')) {
            $stats[] = Stat::make(__('Page views · 30 days'), number_format($totals['views']))
                ->description($trend($totals['views_change']) ?? __('Statistics start with your first visitors'))
                ->descriptionIcon($totals['views_change'] !== null && $totals['views_change'] < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($totals['views_change'] !== null && $totals['views_change'] < 0 ? 'danger' : 'success')
                ->chart(array_slice($series['views'], -14));
            $stats[] = Stat::make(__('Visits · 30 days'), number_format($totals['visits']))
                ->description($trend($totals['visits_change']) ?? __(':count online in last 30 min', ['count' => number_format(PageView::where('created_at', '>=', now()->subMinutes(30))->distinct('visitor_hash')->count('visitor_hash'))]))
                ->chart(array_slice($series['visits'], -14))->color('primary');
        }
        $stats[] = Stat::make(__('Newsletter subscribers'), number_format(Subscriber::active()->count()))
            ->description(__(':count in the last 30 days', ['count' => '+'.number_format($totals['subscribers'])]))->descriptionIcon('heroicon-m-envelope')->color('warning');
        $stats[] = Stat::make(__('Published content'), __(':count cases', ['count' => number_format(ClinicalCase::published()->count())]))
            ->description(__(':articles articles · :videos videos · :questions questions', ['articles' => Article::published()->count(), 'videos' => Video::published()->count(), 'questions' => Question::published()->count()]))
            ->descriptionIcon('heroicon-m-rectangle-stack')->color('gray');

        return $stats;
    }
}
