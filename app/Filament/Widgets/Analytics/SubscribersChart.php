<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\AnalyticsQuery;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class SubscribersChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Newsletter subscribers';

    protected ?string $description = 'Confirmed subscribers over time';

    protected ?string $maxHeight = '240px';

    #[Reactive]
    public int $days = 30;

    protected function getData(): array
    {
        $g = AnalyticsQuery::for($this->days)->subscriberGrowth();

        return [
            'labels' => $g['labels'],
            'datasets' => [['label' => 'Subscribers', 'data' => $g['values'], 'borderColor' => '#d9822b', 'backgroundColor' => 'rgba(217,130,43,.12)', 'fill' => true, 'tension' => .3, 'pointRadius' => 0, 'borderWidth' => 2]],
        ];
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 8]]]];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
