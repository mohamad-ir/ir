@if (! empty($data['heading']))<h2 class="h-section mb-10 text-center">{{ $data['heading'] }}</h2>@endif
<dl class="grid grid-cols-2 gap-px overflow-hidden rounded-card bg-line ring-1 ring-line lg:grid-cols-{{ max(2, min(4, count($data['items'] ?? []))) }}">
    @foreach ($data['items'] ?? [] as $item)
        <div class="{{ $tone === 'ink' ? 'bg-ink' : 'bg-surface' }} px-6 py-8 text-center">
            <dd class="font-heading text-4xl font-semibold tracking-tight {{ $tone === 'ink' ? 'text-white' : 'text-ink' }} sm:text-5xl">{{ $item['value'] ?? '' }}</dd>
            <dt class="mt-2 text-sm font-medium text-muted">{{ $item['label'] ?? '' }}</dt>
        </div>
    @endforeach
</dl>
