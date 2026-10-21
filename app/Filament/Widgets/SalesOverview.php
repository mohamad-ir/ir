<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Webinar;
use App\Services\Payments\StripeClient;
use App\Support\Money;
use App\Support\Settings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Revenue and learning at a glance. Amounts are grouped by the default currency. */
class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Sales & learning';

    public static function canView(): bool
    {
        return auth()->user()?->can('sales.view') ?? false;
    }

    protected function getStats(): array
    {
        $currency = Settings::get('payments.currency', 'EUR');
        $net = fn ($from, $to = null) => (int) Order::whereIn('status', ['paid', 'partially_refunded'])->where('currency', $currency)
            ->where('paid_at', '>=', $from)->when($to, fn ($q) => $q->where('paid_at', '<', $to))
            ->selectRaw('COALESCE(SUM(total - refunded_amount), 0) as net')->value('net');

        $current = $net(now()->subDays(30));
        $previous = $net(now()->subDays(60), now()->subDays(30));
        $change = $previous ? round(($current - $previous) / $previous * 100) : null;

        $daily = collect(range(13, 0))->map(fn ($d) => (int) Order::whereIn('status', ['paid', 'partially_refunded'])->where('currency', $currency)
            ->whereDate('paid_at', now()->subDays($d)->toDateString())->sum('total'))->all();

        $others = Order::whereIn('status', ['paid', 'partially_refunded'])->where('currency', '!=', $currency)->where('paid_at', '>=', now()->subDays(30))
            ->selectRaw('currency, SUM(total - refunded_amount) as net')->groupBy('currency')->pluck('net', 'currency')
            ->map(fn ($net, $code) => '+ '.Money::format((int) $net, $code))->implode(' · ');

        $active = Subscription::grantingAccess()->with('plan')->get();
        $mrr = $active->where('currency', $currency)->sum(fn ($s) => $s->interval === 'year' ? $s->amount / 12 : $s->amount);
        $next = Webinar::published()->where('starts_at', '>', now())->orderBy('starts_at')->first();

        return [
            Stat::make('Revenue · 30 days', Money::format($current, $currency))
                ->description(trim(($others ? $others.' · ' : '').($change === null ? (StripeClient::configured() ? 'Net of refunds' : 'Payments are not switched on yet') : ($change >= 0 ? '+' : '').$change.'% vs previous 30 days')))
                ->descriptionIcon($change !== null && $change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-banknotes')
                ->color($change !== null && $change < 0 ? 'danger' : 'success')->chart($daily),
            Stat::make('Active members', number_format($active->count()))
                ->description('≈ '.Money::format((int) round($mrr), $currency).' monthly recurring revenue')->descriptionIcon('heroicon-m-identification')->color('primary'),
            Stat::make('Course enrolments · 30 days', number_format(Enrollment::where('created_at', '>=', now()->subDays(30))->count()))
                ->description(number_format(Enrollment::whereNotNull('completed_at')->count()).' courses completed in total')->descriptionIcon('heroicon-m-academic-cap')->color('gray'),
            Stat::make('Next webinar', $next ? $next->displayStart('j M, H:i') : '—')
                ->description($next ? $next->activeRegistrations()->count().' registered · '.str($next->title)->limit(40) : 'No webinar scheduled')
                ->descriptionIcon('heroicon-m-video-camera')->color('warning')
                ->url($next ? \App\Filament\Resources\Webinars\WebinarResource::getUrl('edit', ['record' => $next]) : null),
        ];
    }
}
