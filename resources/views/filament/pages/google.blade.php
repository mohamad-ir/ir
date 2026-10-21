@php
    $card = 'rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10';
    $n = fn ($v, $d = 0) => number_format((float) $v, $d);
@endphp
<x-filament-panels::page>
    <div class="flex gap-2">
        @foreach ([7 => '7 days', 28 => '28 days', 90 => '3 months'] as $d => $label)
            <x-filament::button size="sm" :color="$days === $d ? 'primary' : 'gray'" wire:click="$set('days', {{ $d }})">{{ $label }}</x-filament::button>
        @endforeach
    </div>

    @if (! $ga4Configured && ! $gscConfigured)
        <div class="{{ $card }} text-center">
            <x-heroicon-o-link class="mx-auto size-10 text-gray-400" />
            <h3 class="mt-3 text-lg font-semibold">Connect Google Analytics 4 and Search Console</h3>
            <p class="mx-auto mt-1 max-w-xl text-sm text-gray-500">Add a Google service account, your GA4 property ID and Search Console property in Settings › Analytics &amp; Google. Data is read live from Google — nothing is shown until a connection exists.</p>
            <div class="mt-4"><x-filament::button tag="a" :href="\App\Filament\Pages\Settings\AnalyticsSettings::getUrl()">Open connection settings</x-filament::button></div>
        </div>
    @endif

    @if ($ga4Configured)
        <h2 class="text-lg font-semibold">Google Analytics 4</h2>
        @if ($ga4Error)
            <div class="rounded-xl bg-danger-50 p-4 text-sm text-danger-800 dark:bg-danger-500/10 dark:text-danger-300">{{ $ga4Error }}</div>
        @elseif ($ga4)
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([['Active users', $n($ga4['totals']['activeUsers'] ?? 0)], ['Sessions', $n($ga4['totals']['sessions'] ?? 0)], ['Page views', $n($ga4['totals']['screenPageViews'] ?? 0)], ['Avg. session', gmdate('i:s', (int) ($ga4['totals']['averageSessionDuration'] ?? 0))], ['Engagement rate', $n(($ga4['totals']['engagementRate'] ?? 0) * 100, 1).'%']] as [$l, $v])
                    <div class="{{ $card }}"><p class="text-sm text-gray-500">{{ $l }}</p><p class="mt-1 text-3xl font-semibold">{{ $v }}</p></div>
                @endforeach
            </div>
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="{{ $card }} xl:col-span-2"><h3 class="font-semibold">Top pages</h3>
                    @foreach ($ga4['pages'] as $p)<div class="mt-2 flex justify-between gap-4 text-sm"><span class="truncate">{{ $p['pageTitle'] ?: $p['pagePath'] }}</span><span class="tabular-nums text-gray-500">{{ $n($p['screenPageViews']) }}</span></div>@endforeach
                </div>
                <div class="space-y-6">
                    <div class="{{ $card }}"><h3 class="font-semibold">Channels</h3>@foreach ($ga4['sources'] as $s)<div class="mt-2 flex justify-between text-sm"><span>{{ $s['sessionDefaultChannelGroup'] }}</span><span class="tabular-nums text-gray-500">{{ $n($s['sessions']) }}</span></div>@endforeach</div>
                    <div class="{{ $card }}"><h3 class="font-semibold">Countries</h3>@foreach ($ga4['countries'] as $s)<div class="mt-2 flex justify-between text-sm"><span>{{ $s['country'] }}</span><span class="tabular-nums text-gray-500">{{ $n($s['activeUsers']) }}</span></div>@endforeach</div>
                </div>
            </div>
        @endif
    @endif

    @if ($gscConfigured)
        <h2 class="text-lg font-semibold">Google Search Console</h2>
        @if ($gscError)
            <div class="rounded-xl bg-danger-50 p-4 text-sm text-danger-800 dark:bg-danger-500/10 dark:text-danger-300">{{ $gscError }}</div>
        @elseif ($gsc)
            @php($tot = collect($gsc['daily']))
            <div class="grid gap-4 sm:grid-cols-4">
                @foreach ([['Clicks', $n($tot->sum('clicks'))], ['Impressions', $n($tot->sum('impressions'))], ['Average CTR', $tot->sum('impressions') ? $n($tot->sum('clicks') / $tot->sum('impressions') * 100, 1).'%' : '—'], ['Average position', $tot->count() ? $n($tot->avg('position'), 1) : '—']] as [$l, $v])
                    <div class="{{ $card }}"><p class="text-sm text-gray-500">{{ $l }}</p><p class="mt-1 text-3xl font-semibold">{{ $v }}</p></div>
                @endforeach
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                <div class="{{ $card }}"><h3 class="font-semibold">Search queries</h3>
                    <table class="mt-3 w-full text-sm"><thead class="text-left text-xs uppercase text-gray-500"><tr><th class="py-1.5">Query</th><th class="text-right">Clicks</th><th class="text-right">Impr.</th><th class="text-right">Pos.</th></tr></thead>
                    <tbody>@forelse ($gsc['queries'] as $q)<tr class="border-t border-gray-100 dark:border-white/5"><td class="py-1.5">{{ $q['keys'][0] ?? '' }}</td><td class="text-right tabular-nums">{{ $q['clicks'] }}</td><td class="text-right tabular-nums">{{ $n($q['impressions']) }}</td><td class="text-right tabular-nums">{{ $q['position'] }}</td></tr>@empty<tr><td colspan="4" class="py-4 text-center text-gray-400">No search data yet (Search Console data lags by 2–3 days).</td></tr>@endforelse</tbody></table>
                </div>
                <div class="{{ $card }}"><h3 class="font-semibold">Pages in search</h3>
                    @forelse ($gsc['pages'] as $p)<div class="mt-2 flex justify-between gap-4 text-sm"><span class="truncate">{{ str_replace(url('/'), '', $p['keys'][0] ?? '') ?: '/' }}</span><span class="tabular-nums text-gray-500">{{ $p['clicks'] }} clicks</span></div>@empty<p class="mt-3 text-sm text-gray-400">No data yet.</p>@endforelse
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
