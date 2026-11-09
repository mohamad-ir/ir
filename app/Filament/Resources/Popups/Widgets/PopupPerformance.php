<?php

namespace App\Filament\Resources\Popups\Widgets;

use App\Services\Popups\PopupService;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

/** Daily views, clicks and sign-ups of one popup, with a breakdown per device. */
class PopupPerformance extends ChartWidget
{
    public ?Model $record = null;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    public function getHeading(): ?string
    {
        return __('Performance');
    }

    public function getDescription(): ?string
    {
        $devices = app(PopupService::class)->report($this->record, 1)['devices'];
        if (! $devices) {
            return __('Figures appear here once the popup has been shown on the website. Views by staff are not counted when “Exclude staff” is on in analytics settings.');
        }

        $labels = ['desktop' => __('Computers'), 'tablet' => __('Tablets'), 'mobile' => __('Phones')];

        return collect($devices)->map(fn ($d, $key) => ($labels[$key] ?? $key).': '.__(':views views, :clicks clicks', ['views' => number_format($d['views']), 'clicks' => number_format($d['clicks'])]))->implode(' · ');
    }

    protected function getFilters(): ?array
    {
        return ['7' => __('Last 7 days'), '30' => __('Last 30 days'), '90' => __('Last 90 days')];
    }

    protected function getData(): array
    {
        $report = app(PopupService::class)->report($this->record, (int) ($this->filter ?: 30));

        return [
            'labels' => $report['labels'],
            'datasets' => [
                ['label' => __('Views'), 'data' => $report['series']['views'], 'borderColor' => '#0f7f81', 'backgroundColor' => 'rgba(15,127,129,.10)', 'fill' => true, 'tension' => .35, 'pointRadius' => 0, 'borderWidth' => 2],
                ['label' => __('Clicks'), 'data' => $report['series']['clicks'], 'borderColor' => '#12324f', 'backgroundColor' => 'transparent', 'tension' => .35, 'pointRadius' => 0, 'borderWidth' => 2],
                ['label' => __('Sign-ups'), 'data' => $report['series']['conversions'], 'borderColor' => '#d9822b', 'backgroundColor' => 'transparent', 'tension' => .35, 'pointRadius' => 0, 'borderWidth' => 2, 'borderDash' => [4, 4]],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 10]]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
