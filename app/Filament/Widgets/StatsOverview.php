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
        $trend = fn (?float $c) => $c === null ? null : ($c >= 0 ? '+'.$c.'% vs previous 30 days' : $c.'% vs previous 30 days');

        $stats = [];
        if (auth()->user()->can('analytics.view')) {
            $stats[] = Stat::make('Page views · 30 days', number_format($totals['views']))
                ->description($trend($totals['views_change']) ?? 'Statistics start with your first visitors')
                ->descriptionIcon($totals['views_change'] !== null && $totals['views_change'] < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($totals['views_change'] !== null && $totals['views_change'] < 0 ? 'danger' : 'success')
                ->chart(array_slice($series['views'], -14));
            $stats[] = Stat::make('Visits · 30 days', number_format($totals['visits']))
                ->description($trend($totals['visits_change']) ?? number_format(PageView::where('created_at', '>=', now()->subMinutes(30))->distinct('visitor_hash')->count('visitor_hash')).' online in last 30 min')
                ->chart(array_slice($series['visits'], -14))->color('primary');
        }
        $stats[] = Stat::make('Newsletter subscribers', number_format(Subscriber::active()->count()))
            ->description('+'.number_format($totals['subscribers']).' in the last 30 days')->descriptionIcon('heroicon-m-envelope')->color('warning');
        $stats[] = Stat::make('Published content', number_format(ClinicalCase::published()->count()).' cases')
            ->description(Article::published()->count().' articles · '.Video::published()->count().' videos · '.Question::published()->count().' questions')
            ->descriptionIcon('heroicon-m-rectangle-stack')->color('gray');

        return $stats;
    }
}
