@php($entries = $data['entries'] ?? collect())
@php($layout = $data['layout'] ?? 'timeline')
@if (! empty($data['heading']))<h2 class="h-section mb-10">{{ $data['heading'] }}</h2>@endif
@if ($entries->isEmpty())
    <p class="text-muted">Entries can be added under Content › Profile &amp; CV.</p>
@elseif ($layout === 'cards')
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($entries as $entry)
            <div class="card p-6">
                @if ($entry->period())<p class="text-sm font-semibold tabular-nums text-accent-ink">{{ $entry->period() }}</p>@endif
                <h3 class="h-card mt-2 !text-lg">{{ $entry->title }}</h3>
                @if ($entry->subtitle)<p class="mt-1 text-[0.9375rem] font-medium text-fg/80">{{ $entry->subtitle }}</p>@endif
                @if ($entry->location)<p class="text-sm text-muted">{{ $entry->location }}</p>@endif
                @if ($entry->description)<p class="mt-3 text-sm leading-relaxed text-muted">{{ $entry->description }}</p>@endif
            </div>
        @endforeach
    </div>
@elseif ($layout === 'list')
    <ul class="divide-y divide-line border-y border-line" role="list">
        @foreach ($entries as $entry)
            <li class="flex flex-col gap-1 py-4 sm:flex-row sm:items-baseline sm:gap-8">
                <span class="w-32 shrink-0 text-sm font-semibold tabular-nums text-accent-ink">{{ $entry->period() }}</span>
                <span><span class="font-semibold text-ink">{{ $entry->title }}</span>@if($entry->subtitle)<span class="text-muted"> — {{ $entry->subtitle }}</span>@endif</span>
            </li>
        @endforeach
    </ul>
@else
    <ol class="relative ml-3 border-l border-line-strong" role="list">
        @foreach ($entries as $entry)
            <li class="relative pb-10 pl-8 last:pb-0 reveal-on-scroll">
                <span class="absolute -left-[7px] top-1.5 size-3.5 rounded-full border-2 border-accent bg-paper"></span>
                @if ($entry->period())<p class="text-sm font-semibold tabular-nums text-accent-ink">{{ $entry->period() }}</p>@endif
                <h3 class="mt-1 font-heading text-xl font-semibold text-ink">{{ $entry->title }}</h3>
                @if ($entry->subtitle || $entry->location)<p class="mt-0.5 text-[0.9375rem] text-muted">{{ collect([$entry->subtitle, $entry->location])->filter()->implode(' · ') }}</p>@endif
                @if ($entry->description)<p class="mt-2 max-w-2xl text-[0.9375rem] leading-relaxed text-fg/85">{{ $entry->description }}</p>@endif
            </li>
        @endforeach
    </ol>
@endif
