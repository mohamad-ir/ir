@php($cols = (int) ($data['columns'] ?? 3))
<x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" />
<div class="grid gap-5 sm:grid-cols-2 {{ $cols === 4 ? 'lg:grid-cols-4' : ($cols === 2 ? '' : 'lg:grid-cols-3') }}">
    @foreach ($data['items'] ?? [] as $item)
        @php($tag = ! empty($item['url']) ? 'a' : 'div')
        <{{ $tag }} @if($tag === 'a') href="{{ $item['url'] }}" @endif class="{{ $tag === 'a' ? 'card-interactive' : 'card' }} group relative p-6 reveal-on-scroll" style="transition-delay: {{ $loop->index * 60 }}ms">
            <span class="grid size-11 place-items-center rounded-ui bg-accent/10 text-accent-ink ring-1 ring-accent/15">
                <x-dynamic-component :component="'heroicon-o-'.(array_key_exists($item['icon'] ?? '', \App\Services\PageBuilder\Blocks\FeatureGridBlock::ICONS) ? $item['icon'] : 'eye')" class="size-5.5" />
            </span>
            <h3 class="h-card mt-5 {{ $tone === 'ink' ? 'text-white' : 'text-ink' }}">{{ $item['title'] ?? '' }}</h3>
            @if (! empty($item['text']))<p class="mt-2 text-[0.9375rem] leading-relaxed text-muted">{{ $item['text'] }}</p>@endif
            @if ($tag === 'a')<span class="link-arrow mt-4">Explore <x-heroicon-m-arrow-right /></span>@endif
        </{{ $tag }}>
    @endforeach
</div>
