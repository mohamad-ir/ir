<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\AnalyticsQuery;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class ChannelsChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Traffic sources';

    protected ?string $description = 'How new visits arrived';

    protected ?string $maxHeight = '240px';

    #[Reactive]
    public int $days = 30;

    #[Reactive]
    public string $dimension = 'channels';

    protected function getData(): array
    {
        $q = AnalyticsQuery::for($this->days);
        $data = $this->dimension === 'channels'
            ? $q->channels()
            : $q->breakdown($this->dimension, 6)->pluck('visits', 'label')->all();

        return [
            'labels' => array_keys($data) ?: ['No data yet'],
            'datasets' => [[
                'data' => array_values($data) ?: [1],
                'backgroundColor' => $data ? ['#12324f', '#0f7f81', '#d9822b', '#6b4fa0', '#b4413b', '#3a6b35', '#8a96a3'] : ['#e5e7eb'],
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return ['cutout' => '68%', 'plugins' => ['legend' => ['position' => 'right', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]], 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getHeading(): ?string
    {
        return ['channels' => 'Traffic sources', 'device' => 'Devices', 'country' => 'Countries', 'browser' => 'Browsers'][$this->dimension] ?? 'Breakdown';
    }

    public function getDescription(): ?string
    {
        return $this->dimension === 'channels' ? 'How new visits arrived' : 'Share of visits';
    }
}
