<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\ClinicalCase;
use App\Models\PageView;
use App\Models\SearchQuery;
use App\Models\Subscriber;
use App\Models\Video;
use App\Support\Countries;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Aggregates first-party analytics for the dashboard. */
class AnalyticsQuery
{
    public Carbon $from;

    public Carbon $to;

    public function __construct(int $days = 30)
    {
        $this->to = now()->endOfDay();
        $this->from = now()->subDays(max(1, $days) - 1)->startOfDay();
    }

    public static function for(int $days): static
    {
        return new static($days);
    }

    protected function views(?Carbon $from = null, ?Carbon $to = null)
    {
        return PageView::query()->whereBetween('created_at', [$from ?? $this->from, $to ?? $this->to]);
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    /** Visits = unique (visitor, day) pairs; the visitor hash rotates daily by design. */
    protected function visitsCount($query): int
    {
        return (int) (clone $query)->toBase()->selectRaw('COUNT(DISTINCT visitor_hash, DATE(created_at)) as c')->value('c');
    }

    public function totals(): array
    {
        $prevFrom = $this->from->copy()->subDays($this->days());
        $prevTo = $this->from->copy()->subSecond();

        $views = $this->views()->count();
        $visits = $this->visitsCount($this->views());
        $prevViews = $this->views($prevFrom, $prevTo)->count();
        $prevVisits = $this->visitsCount($this->views($prevFrom, $prevTo));

        $subs = Subscriber::where('status', 'subscribed')->whereBetween('confirmed_at', [$this->from, $this->to])->count();
        $prevSubs = Subscriber::where('status', 'subscribed')->whereBetween('confirmed_at', [$prevFrom, $prevTo])->count();

        return [
            'views' => $views,
            'visits' => $visits,
            'pages_per_visit' => $visits ? round($views / $visits, 1) : 0,
            'subscribers' => $subs,
            'views_change' => $this->change($views, $prevViews),
            'visits_change' => $this->change($visits, $prevVisits),
            'subscribers_change' => $this->change($subs, $prevSubs),
            'reveals' => AnalyticsEvent::where('name', 'case_reveal')->whereBetween('created_at', [$this->from, $this->to])->count(),
            'plays' => AnalyticsEvent::where('name', 'video_play')->whereBetween('created_at', [$this->from, $this->to])->count(),
        ];
    }

    protected function change(int|float $now, int|float $before): ?float
    {
        return $before > 0 ? round(($now - $before) / $before * 100, 1) : null;
    }

    /** @return array{labels: array, views: array, visits: array} */
    public function timeseries(): array
    {
        $rows = $this->views()->toBase()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as v, COUNT(DISTINCT visitor_hash) as u')
            ->groupBy('d')->get()->keyBy('d');

        $labels = $views = $visits = [];
        foreach (CarbonPeriod::create($this->from, '1 day', $this->to->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            $labels[] = $day->format('j M');
            $views[] = (int) ($rows[$key]->v ?? 0);
            $visits[] = (int) ($rows[$key]->u ?? 0);
        }

        return compact('labels', 'views', 'visits');
    }

    public function topPages(int $limit = 10): Collection
    {
        return $this->views()->toBase()
            ->selectRaw('path, viewable_type, viewable_id, COUNT(*) as views, COUNT(DISTINCT visitor_hash, DATE(created_at)) as visits')
            ->groupBy('path', 'viewable_type', 'viewable_id')->orderByDesc('views')->limit($limit)->get()
            ->map(function ($row) {
                $title = $row->path === '/' ? 'Homepage' : null;
                if (! $title && $row->viewable_type && ($class = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($row->viewable_type))) {
                    $model = method_exists($class, 'withTrashed') ? $class::withTrashed()->find($row->viewable_id) : $class::find($row->viewable_id);
                    $title = $model?->title ?? $model?->name;
                }
                $row->title = $title ?: $row->path;

                return $row;
            });
    }

    public function topCases(int $limit = 8): Collection
    {
        $views = $this->views()->where('viewable_type', 'case')->toBase()
            ->selectRaw('viewable_id, COUNT(*) as views')->groupBy('viewable_id')->orderByDesc('views')->limit($limit)->pluck('views', 'viewable_id');

        $events = AnalyticsEvent::query()->where('subject_type', 'case')->whereIn('subject_id', $views->keys())->whereBetween('created_at', [$this->from, $this->to])->toBase()
            ->selectRaw("subject_id, SUM(name = 'case_reveal') as reveals, SUM(name = 'case_guess') as guesses, SUM(name = 'case_guess' AND JSON_UNQUOTE(JSON_EXTRACT(properties, '$.correct')) IN ('1','true')) as correct")
            ->groupBy('subject_id')->get()->keyBy('subject_id');

        $cases = ClinicalCase::withTrashed()->whereIn('id', $views->keys())->get()->keyBy('id');

        return $views->map(fn ($v, $id) => (object) [
            'case' => $cases[$id] ?? null,
            'views' => (int) $v,
            'reveals' => (int) ($events[$id]->reveals ?? 0),
            'guesses' => (int) ($events[$id]->guesses ?? 0),
            'correct_rate' => ($events[$id]->guesses ?? 0) ? round($events[$id]->correct / $events[$id]->guesses * 100) : null,
        ])->filter(fn ($r) => $r->case)->values();
    }

    public function videoEngagement(int $limit = 8): Collection
    {
        $rows = AnalyticsEvent::query()->where('subject_type', 'video')->whereBetween('created_at', [$this->from, $this->to])->toBase()
            ->selectRaw("subject_id, SUM(name = 'video_play') as plays, SUM(name = 'video_progress' AND JSON_UNQUOTE(JSON_EXTRACT(properties, '$.percent')) = '50') as half, SUM(name = 'video_complete') as completes")
            ->groupBy('subject_id')->orderByDesc('plays')->limit($limit)->get();
        $videos = Video::withTrashed()->whereIn('id', $rows->pluck('subject_id'))->get()->keyBy('id');

        return $rows->map(fn ($r) => (object) [
            'video' => $videos[$r->subject_id] ?? null,
            'plays' => (int) $r->plays,
            'half_rate' => $r->plays ? round($r->half / $r->plays * 100) : 0,
            'completion_rate' => $r->plays ? round($r->completes / $r->plays * 100) : 0,
        ])->filter(fn ($r) => $r->video)->values();
    }

    public static function channelFor(?string $host, ?string $utmSource, ?string $utmMedium): string
    {
        $medium = strtolower((string) $utmMedium);
        $source = strtolower((string) ($utmSource ?: $host));

        return match (true) {
            in_array($medium, ['email', 'newsletter'], true) || str_contains($source, 'newsletter') || str_contains($source, 'mail.') => 'Email',
            in_array($medium, ['cpc', 'ppc', 'paid'], true) => 'Paid',
            (bool) preg_match('/(^|\.)(google|bing|duckduckgo|yahoo|ecosia|baidu|yandex|startpage|brave)\./', $source.'.') || in_array($source, ['google', 'bing'], true) => 'Search',
            (bool) preg_match('/(linkedin|lnkd|facebook|fb\.|instagram|t\.co|twitter|x\.com|whatsapp|reddit|youtube|threads|bsky|mastodon)/', $source) => 'Social',
            $source === '' => 'Direct',
            default => 'Referral',
        };
    }

    public function channels(): array
    {
        $rows = $this->views()->where('is_unique', true)->toBase()
            ->selectRaw('referrer_host, utm_source, utm_medium, COUNT(*) as c')->groupBy('referrer_host', 'utm_source', 'utm_medium')->get();

        $out = ['Direct' => 0, 'Search' => 0, 'Social' => 0, 'Email' => 0, 'Referral' => 0, 'Paid' => 0];
        foreach ($rows as $r) {
            $out[static::channelFor($r->referrer_host, $r->utm_source, $r->utm_medium)] += (int) $r->c;
        }

        return array_filter($out);
    }

    public function referrers(int $limit = 10): Collection
    {
        return $this->views()->whereNotNull('referrer_host')->toBase()
            ->selectRaw('referrer_host as host, COUNT(*) as views, COUNT(DISTINCT visitor_hash, DATE(created_at)) as visits')
            ->groupBy('referrer_host')->orderByDesc('visits')->limit($limit)->get();
    }

    public function campaigns(int $limit = 8): Collection
    {
        return $this->views()->whereNotNull('utm_source')->toBase()
            ->selectRaw('utm_source, utm_medium, utm_campaign, COUNT(DISTINCT visitor_hash, DATE(created_at)) as visits')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')->orderByDesc('visits')->limit($limit)->get();
    }

    public function breakdown(string $column, int $limit = 8): Collection
    {
        abort_unless(in_array($column, ['device', 'browser', 'os', 'country'], true), 400);

        return $this->views()->toBase()
            ->selectRaw("{$column} as label, COUNT(DISTINCT visitor_hash, DATE(created_at)) as visits")
            ->groupBy($column)->orderByDesc('visits')->limit($limit)->get()
            ->map(function ($r) use ($column) {
                $r->label = $column === 'country' ? ($r->label ? Countries::name($r->label) : 'Unknown') : ($r->label ? ucfirst($r->label) : 'Unknown');

                return $r;
            });
    }

    public function searches(int $limit = 10): array
    {
        $base = SearchQuery::query()->whereBetween('created_at', [$this->from, $this->to]);

        return [
            'top' => (clone $base)->toBase()->selectRaw('query, COUNT(*) as c, MAX(results_count) as results')->groupBy('query')->orderByDesc('c')->limit($limit)->get(),
            'zero' => (clone $base)->where('results_count', 0)->toBase()->selectRaw('query, COUNT(*) as c')->groupBy('query')->orderByDesc('c')->limit($limit)->get(),
            'total' => (clone $base)->count(),
        ];
    }

    public function shares(): Collection
    {
        return AnalyticsEvent::where('name', 'share')->whereBetween('created_at', [$this->from, $this->to])->toBase()
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.network')) as network, COUNT(*) as c")->groupBy('network')->orderByDesc('c')->get();
    }

    /** Cumulative confirmed subscribers per day. */
    public function subscriberGrowth(): array
    {
        $start = Subscriber::where('status', 'subscribed')->where('confirmed_at', '<', $this->from)->count();
        $daily = Subscriber::where('status', 'subscribed')->whereBetween('confirmed_at', [$this->from, $this->to])->toBase()
            ->selectRaw('DATE(confirmed_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');

        $labels = $values = [];
        $running = $start;
        foreach (CarbonPeriod::create($this->from, '1 day', $this->to->copy()->startOfDay()) as $day) {
            $running += (int) ($daily[$day->toDateString()] ?? 0);
            $labels[] = $day->format('j M');
            $values[] = $running;
        }

        return compact('labels', 'values');
    }

    public function realtime(): int
    {
        return PageView::where('created_at', '>=', now()->subMinutes(30))->distinct('visitor_hash')->count('visitor_hash');
    }

    /** Deletes raw rows older than the configured retention. */
    public static function prune(int $days): int
    {
        $cutoff = now()->subDays(max(30, $days));
        $n = PageView::where('created_at', '<', $cutoff)->delete();
        $n += AnalyticsEvent::where('created_at', '<', $cutoff)->delete();
        $n += SearchQuery::where('created_at', '<', $cutoff)->delete();

        return $n;
    }
}
