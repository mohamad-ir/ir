<x-section-heading :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" />
<ol class="relative ml-3 border-l border-line-strong" role="list">
    @foreach ($data['items'] ?? [] as $item)
        <li class="relative pb-10 pl-8 last:pb-0 reveal-on-scroll">
            <span class="absolute -left-[7px] top-1.5 size-3.5 rounded-full border-2 border-accent bg-paper"></span>
            @if (! empty($item['period']))<p class="text-sm font-semibold tabular-nums text-accent-ink">{{ $item['period'] }}</p>@endif
            <h3 class="mt-1 font-heading text-xl font-semibold {{ $tone === 'ink' ? 'text-white' : 'text-ink' }}">{{ $item['title'] ?? '' }}</h3>
            @if (! empty($item['subtitle']))<p class="mt-0.5 text-muted">{{ $item['subtitle'] }}</p>@endif
            @if (! empty($item['text']))<p class="mt-2 max-w-2xl text-[0.9375rem] leading-relaxed text-muted">{{ $item['text'] }}</p>@endif
        </li>
    @endforeach
</ol>
