<?php

namespace App\Filament\Resources\Popups\Widgets;

use App\Models\Popup;
use App\Services\Popups\PopupService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/** Totals for one popup on its edit page. */
class PopupStatsOverview extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        /** @var Popup $popup */
        $popup = $this->record->fresh();
        $report = app(PopupService::class)->report($popup, 14);
        $pct = fn (int $part, int $whole) => $whole ? round($part / $whole * 100, 1).'%' : '—';
        $since = $popup->stats_reset_at ? __('since :date', ['date' => $popup->stats_reset_at->format('j M Y')]) : __('all time');

        return [
            Stat::make(__('Views'), number_format($popup->views_count))->description($since)
                ->chart($report['series']['views'])->color('primary'),
            Stat::make(__('Clicks'), number_format($popup->clicks_count))->description(__(':rate click-through rate', ['rate' => $pct($popup->clicks_count, $popup->views_count)]))
                ->chart($report['series']['clicks'])->color('success'),
            Stat::make(__('Sign-ups'), number_format($popup->conversions_count))->description($popup->form === 'newsletter' ? __(':rate of views', ['rate' => $pct($popup->conversions_count, $popup->views_count)]) : __('Only for popups with a form'))
                ->chart($report['series']['conversions'])->color('info'),
            Stat::make(__('Closed'), number_format($popup->closes_count))->description(__(':rate of views', ['rate' => $pct($popup->closes_count, $popup->views_count)]))
                ->chart($report['series']['closes'])->color('gray'),
        ];
    }
}
