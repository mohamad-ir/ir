<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\AnalyticsQuery;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class TrafficChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Traffic';

    protected ?string $description = 'Page views and visits per day';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public int $days = 30;

    protected function getData(): array
    {
        $series = AnalyticsQuery::for($this->days)->timeseries();

        return [
            'labels' => $series['labels'],
            'datasets' => [
                ['label' => 'Page views', 'data' => $series['views'], 'borderColor' => '#0f7f81', 'backgroundColor' => 'rgba(15,127,129,.10)', 'fill' => true, 'tension' => .35, 'pointRadius' => 0, 'borderWidth' => 2],
                ['label' => 'Visits', 'data' => $series['visits'], 'borderColor' => '#12324f', 'backgroundColor' => 'transparent', 'tension' => .35, 'pointRadius' => 0, 'borderWidth' => 2, 'borderDash' => [4, 4]],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => 'rgba(0,0,0,.05)']], 'x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 10]]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
