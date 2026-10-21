@php
    $fmt = fn ($n) => number_format((float) $n);
    $delta = function ($v) {
        if ($v === null) return '<span class="text-xs text-gray-400">no previous data</span>';
        $up = $v >= 0;
        return '<span class="text-xs font-semibold '.($up ? 'text-success-600' : 'text-danger-600').'">'.($up ? '▲' : '▼').' '.abs($v).'%</span> <span class="text-xs text-gray-400">vs previous period</span>';
    };
    $card = 'rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10';
@endphp
<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-2">
        @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months'] as $d => $label)
            <x-filament::button size="sm" :color="$days === $d ? 'primary' : 'gray'" wire:click="$set('days', {{ $d }})">{{ $label }}</x-filament::button>
        @endforeach
        <span wire:loading class="ml-2 text-sm text-gray-500">Updating…</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['Page views', $fmt($totals['views']), $totals['views_change']],
            ['Visits', $fmt($totals['visits']), $totals['visits_change']],
            ['Pages per visit', $totals['pages_per_visit'], null],
            ['Case answers revealed', $fmt($totals['reveals']), null],
            ['New subscribers', $fmt($totals['subscribers']), $totals['subscribers_change']],
        ] as [$label, $value, $change])
            <div class="{{ $card }}">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 font-[var(--font-display)] text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $value }}</p>
                @if ($change !== null || in_array($label, ['Page views', 'Visits', 'New subscribers'])) <p class="mt-1">{!! $delta($change) !!}</p> @endif
            </div>
        @endforeach
    </div>

    @livewire(\App\Filament\Widgets\Analytics\TrafficChart::class, ['days' => $days], key('traffic'))

    <div class="grid gap-6 lg:grid-cols-3">
        @livewire(\App\Filament\Widgets\Analytics\ChannelsChart::class, ['days' => $days, 'dimension' => 'channels'], key('channels'))
        @livewire(\App\Filament\Widgets\Analytics\ChannelsChart::class, ['days' => $days, 'dimension' => 'device'], key('devices'))
        @livewire(\App\Filament\Widgets\Analytics\SubscribersChart::class, ['days' => $days], key('subs'))
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="{{ $card }}">
            <h3 class="font-semibold">Most visited pages</h3>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-xs uppercase text-gray-500"><tr><th class="py-2 font-medium">Page</th><th class="py-2 text-right font-medium">Views</th><th class="py-2 text-right font-medium">Visits</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($pages as $p)
                        <tr><td class="py-2 pr-3"><a href="{{ url($p->path) }}" target="_blank" class="font-medium hover:text-primary-600">{{ \Illuminate\Support\Str::limit($p->title, 60) }}</a><div class="text-xs text-gray-400">{{ $p->path }}</div></td><td class="py-2 text-right tabular-nums">{{ $fmt($p->views) }}</td><td class="py-2 text-right tabular-nums">{{ $fmt($p->visits) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-400">No visits recorded in this period yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="{{ $card }}">
            <h3 class="font-semibold">Popular OCT cases</h3>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-xs uppercase text-gray-500"><tr><th class="py-2 font-medium">Case</th><th class="py-2 text-right font-medium">Views</th><th class="py-2 text-right font-medium">Reveals</th><th class="py-2 text-right font-medium">Correct</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($cases as $c)
                        <tr><td class="py-2 pr-3 font-medium">{{ \Illuminate\Support\Str::limit($c->case->title, 55) }}</td><td class="py-2 text-right tabular-nums">{{ $fmt($c->views) }}</td><td class="py-2 text-right tabular-nums">{{ $fmt($c->reveals) }}</td><td class="py-2 text-right tabular-nums">{{ $c->correct_rate !== null ? $c->correct_rate.'%' : '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-gray-400">No case views yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="{{ $card }}">
            <h3 class="font-semibold">Video engagement</h3>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-xs uppercase text-gray-500"><tr><th class="py-2 font-medium">Video</th><th class="py-2 text-right font-medium">Plays</th><th class="py-2 text-right font-medium">Reached 50%</th><th class="py-2 text-right font-medium">Completed</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($videos as $v)
                        <tr><td class="py-2 pr-3 font-medium">{{ \Illuminate\Support\Str::limit($v->video->title, 50) }}</td><td class="py-2 text-right tabular-nums">{{ $fmt($v->plays) }}</td><td class="py-2 text-right tabular-nums">{{ $v->half_rate }}%</td><td class="py-2 text-right tabular-nums">{{ $v->completion_rate }}%</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-gray-400">No video plays yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="{{ $card }}">
            <h3 class="font-semibold">Referring websites</h3>
            <table class="mt-3 w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($referrers as $r)
                        <tr><td class="py-2 pr-3"><span class="font-medium">{{ $r->host }}</span> <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500 dark:bg-white/10">{{ \App\Services\Analytics\AnalyticsQuery::channelFor($r->host, null, null) }}</span></td><td class="py-2 text-right tabular-nums">{{ $fmt($r->visits) }} visits</td></tr>
                    @empty
                        <tr><td class="py-6 text-center text-gray-400">No referrals yet — visits so far are direct.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($campaigns->isNotEmpty())
                <h4 class="mt-5 text-xs font-semibold uppercase text-gray-500">Campaign links (UTM)</h4>
                @foreach ($campaigns as $c)
                    <div class="mt-1 flex justify-between text-sm"><span>{{ $c->utm_source }} / {{ $c->utm_medium ?: '—' }} @if($c->utm_campaign)· {{ $c->utm_campaign }}@endif</span><span class="tabular-nums">{{ $fmt($c->visits) }}</span></div>
                @endforeach
            @endif
        </div>
        <div class="{{ $card }}">
            <h3 class="font-semibold">Countries</h3>
            @if (! $geoAvailable)
                <p class="mt-2 text-xs text-gray-500">Upload the free GeoLite2 database in Settings › Analytics &amp; Google (or use Cloudflare) to see countries.</p>
            @endif
            @php($max = max(1, $countries->max('visits') ?? 1))
            <div class="mt-3 space-y-2">
                @forelse ($countries as $c)
                    <div class="text-sm"><div class="flex justify-between"><span>{{ $c->label }}</span><span class="tabular-nums text-gray-500">{{ $fmt($c->visits) }}</span></div><div class="mt-1 h-1.5 rounded-full bg-gray-100 dark:bg-white/10"><div class="h-full rounded-full bg-primary-600" style="width: {{ round($c->visits / $max * 100) }}%"></div></div></div>
                @empty
                    <p class="py-4 text-center text-sm text-gray-400">No data yet.</p>
                @endforelse
            </div>
        </div>
        <div class="{{ $card }}">
            <h3 class="font-semibold">On-site searches <span class="text-sm font-normal text-gray-500">({{ $fmt($searches['total']) }})</span></h3>
            <div class="mt-3 grid gap-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-500">Top searches</p>
                    @forelse ($searches['top'] as $s)<div class="mt-1.5 flex justify-between text-sm"><span>{{ $s->query }}</span><span class="tabular-nums text-gray-500">{{ $s->c }}</span></div>@empty<p class="mt-2 text-sm text-gray-400">None yet.</p>@endforelse
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-gray-500">No results — content ideas</p>
                    @forelse ($searches['zero'] as $s)<div class="mt-1.5 flex justify-between text-sm"><span class="text-danger-600">{{ $s->query }}</span><span class="tabular-nums text-gray-500">{{ $s->c }}</span></div>@empty<p class="mt-2 text-sm text-gray-400">Every search found results.</p>@endforelse
                </div>
            </div>
            @if ($shares->isNotEmpty())
                <p class="mt-5 text-xs font-semibold uppercase text-gray-500">Shares</p>
                <div class="mt-2 flex flex-wrap gap-2">@foreach ($shares as $s)<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs dark:bg-white/10">{{ ucfirst($s->network) }}: {{ $s->c }}</span>@endforeach</div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
