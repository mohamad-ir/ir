@if (! empty($data['heading']))<p class="mb-8 text-center text-xs font-semibold uppercase tracking-[.16em] text-muted">{{ $data['heading'] }}</p>@endif
<ul class="flex flex-wrap items-center justify-center gap-x-12 gap-y-8" role="list">
    @foreach ($data['items'] ?? [] as $item)
        <li>
            @php($tag = ! empty($item['url']) ? 'a' : 'span')
            <{{ $tag }} @if($tag === 'a') href="{{ $item['url'] }}" target="_blank" rel="noopener" @endif class="block transition {{ ! empty($data['grayscale']) ? 'opacity-60 grayscale hover:opacity-100 hover:grayscale-0' : '' }}" title="{{ $item['name'] ?? '' }}">
                @if ($item['media'] ?? null)
                    <img src="{{ $item['media']->url('thumb') }}" alt="{{ $item['name'] ?? '' }}" class="h-10 w-auto max-w-[10rem] object-contain sm:h-12" loading="lazy">
                @else
                    <span class="font-heading text-lg font-semibold {{ $tone === 'ink' ? 'text-white/80' : 'text-ink/70' }}">{{ $item['name'] ?? '' }}</span>
                @endif
            </{{ $tag }}>
        </li>
    @endforeach
</ul>
