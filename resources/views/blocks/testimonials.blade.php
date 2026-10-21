<x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" align="center" />
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
    @foreach ($data['items'] ?? [] as $item)
        <figure class="card flex flex-col p-7 reveal-on-scroll">
            <svg class="size-8 text-accent/40" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M9.3 24.8c-2.6 0-4.6-2.1-4.6-5.2 0-4.8 3.6-9.3 8.7-11.2l1 1.6c-3 1.4-4.9 3.9-5 6.3 2.4-.1 4.3 1.8 4.3 4.2 0 2.5-2 4.3-4.4 4.3zm13.4 0c-2.6 0-4.6-2.1-4.6-5.2 0-4.8 3.6-9.3 8.7-11.2l1 1.6c-3 1.4-4.9 3.9-5 6.3 2.4-.1 4.3 1.8 4.3 4.2 0 2.5-2 4.3-4.4 4.3z"/></svg>
            <blockquote class="mt-4 flex-1 font-heading text-lg leading-relaxed {{ $tone === 'ink' ? 'text-white/90' : 'text-fg' }}">{{ $item['quote'] ?? '' }}</blockquote>
            <figcaption class="mt-6 flex items-center gap-3">
                @if ($item['photo'] ?? null)
                    <img src="{{ $item['photo']->thumbUrl() }}" alt="" class="size-11 rounded-full object-cover" loading="lazy">
                @else
                    <span class="grid size-11 place-items-center rounded-full bg-accent/15 font-semibold text-accent-ink">{{ mb_substr($item['name'] ?? '?', 0, 1) }}</span>
                @endif
                <span><span class="block font-semibold">{{ $item['name'] ?? '' }}</span><span class="block text-sm text-muted">{{ $item['role'] ?? '' }}</span></span>
            </figcaption>
        </figure>
    @endforeach
</div>
