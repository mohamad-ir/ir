@php($card = 'rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10')
<x-filament-panels::page>
    @if ($maintenance)
        <div class="rounded-xl bg-warning-50 p-4 text-sm font-medium text-warning-800 ring-1 ring-warning-200">The website is in maintenance mode — visitors see a “back shortly” page.</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach (['Runtime status' => $runtime, 'Server requirements' => $requirements] as $title => $checks)
            <div class="{{ $card }}">
                <h3 class="border-b border-gray-100 px-5 py-3 font-semibold dark:border-white/5">{{ $title }}</h3>
                <ul class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($checks as $c)
                        <li class="flex items-start gap-3 px-5 py-2.5 text-sm">
                            @if ($c['ok'])<x-heroicon-s-check-circle class="mt-0.5 size-5 shrink-0 text-success-500" />@else<x-heroicon-s-exclamation-triangle class="mt-0.5 size-5 shrink-0 text-warning-500" />@endif
                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between gap-4"><span class="font-medium">{{ $c['label'] }}</span><span class="truncate text-gray-500">{{ $c['value'] }}</span></div>
                                @if (! $c['ok'] && ! empty($c['help']))<p class="mt-0.5 text-xs text-warning-700 dark:text-warning-400">{{ $c['help'] }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="{{ $card }} p-5">
        <h3 class="font-semibold">Maintenance tools</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['clearCaches', 'Clear all caches', 'Use after editing files on the server or if changes do not appear.', 'heroicon-o-sparkles'],
                ['optimize', 'Optimise for speed', 'Caches configuration, routes and views. Run after each update.', 'heroicon-o-bolt'],
                ['rebuildSearch', 'Rebuild search index', 'Re-indexes every case, article, video and question.', 'heroicon-o-magnifying-glass'],
                ['runScheduler', 'Run scheduled tasks now', 'Sends pending newsletter batches and runs due backups.', 'heroicon-o-play'],
                ['pruneAnalytics', 'Prune old analytics', 'Deletes raw statistics older than the retention period.', 'heroicon-o-scissors'],
            ] as [$method, $label, $help, $icon])
                <button type="button" wire:click="{{ $method }}" wire:loading.attr="disabled" class="flex items-start gap-3 rounded-xl border border-gray-200 p-4 text-left transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-white/10 dark:hover:bg-white/5">
                    <x-dynamic-component :component="$icon" class="size-5 shrink-0 text-primary-600" />
                    <span><span class="block text-sm font-semibold">{{ $label }}</span><span class="mt-0.5 block text-xs text-gray-500">{{ $help }}</span></span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="{{ $card }} p-5 text-sm">
            <h3 class="font-semibold">Cron job</h3>
            <p class="mt-2 text-gray-500">In DirectAdmin › Advanced Features › Cron Jobs, add a job that runs every minute:</p>
            <code class="mt-2 block overflow-x-auto rounded-lg bg-gray-950 p-3 text-xs text-gray-100">{{ $cronLine }}</code>
            <p class="mt-4 text-gray-500">No cron access? Use a free external service (e.g. cron-job.org) to call this address every minute:</p>
            @if ($cronUrl)
                <code class="mt-2 block overflow-x-auto rounded-lg bg-gray-100 p-3 text-xs dark:bg-white/5">{{ $cronUrl }}</code>
            @endif
            <x-filament::button size="sm" color="gray" class="mt-3" wire:click="generateCronToken">{{ $cronUrl ? 'Generate a new address' : 'Generate web-cron address' }}</x-filament::button>
        </div>
        <div class="{{ $card }} p-5 text-sm">
            <h3 class="font-semibold">Sample content</h3>
            @if ($demo)
                <p class="mt-2 text-gray-500">This site still contains sample content from the installer:</p>
                <ul class="mt-2 list-disc pl-5 text-gray-600 dark:text-gray-300">
                    @foreach ($demo as $type => $count)<li>{{ $count }} × {{ str($type)->replace('_', ' ')->plural($count) }}</li>@endforeach
                </ul>
                <p class="mt-3 text-gray-500">Remove it with the button at the top of this page once your own content is ready.</p>
            @else
                <p class="mt-2 text-gray-500">No sample content remains.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
