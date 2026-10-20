@php
    $items = $data['items'] ?? [];
    $cols = (int) ($data['columns'] ?? 3);
    $dark = ($data['style'] ?? 'light') === 'dark';
    $viewerImages = collect($items)->map(fn ($i) => ['src' => $i['media']->url('lg'), 'full' => $i['media']->url(), 'caption' => $i['caption'] ?: $i['media']->caption, 'alt' => $i['media']->altText()])->values();
@endphp
<div x-data="caseViewer(@js($viewerImages))" x-on:keydown.window="key($event)">
    @if (! empty($data['heading']))<h2 class="h-section mb-8">{{ $data['heading'] }}</h2>@endif
    <div class="grid gap-4 sm:grid-cols-2 {{ $cols === 4 ? 'lg:grid-cols-4' : ($cols === 2 ? '' : 'lg:grid-cols-3') }}">
        @foreach ($items as $i => $item)
            <figure>
                <button type="button" x-on:click="show({{ $i }})" class="group block w-full overflow-hidden {{ $dark ? 'viewer-frame' : 'rounded-card bg-subtle ring-1 ring-line' }}">
                    <x-media-img :media="$item['media']" variant="md" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                </button>
                @if ($item['caption'] ?: $item['media']->caption)
                    <figcaption class="mt-2 text-sm text-muted">{{ $item['caption'] ?: $item['media']->caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
    @include('cases.partials.lightbox')
</div>
