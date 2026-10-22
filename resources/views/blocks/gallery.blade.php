@if (($data['source'] ?? 'images') === 'album')
    @if (! empty($data['album']))
        <x-section-heading :heading="($data['heading'] ?? null) ?: $data['album']->title" :intro="$data['album']->summary" action-label="View the full gallery" :action-url="$data['album']->urlPath()" />
        @include('galleries.grid', ['items' => $data['album_items'], 'layout' => $data['album']->layout])
    @elseif ($context['preview'] ?? false)
        <p class="rounded-ui bg-subtle p-6 text-center text-sm text-muted">Choose a published photo gallery for this section.</p>
    @endif
@else
    @php $items = collect($data['items'] ?? [])->filter(fn ($i) => $i['media'] ?? null)->values(); @endphp
    @if ($items->isNotEmpty())
        @if (! empty($data['heading']))<h2 class="h-section mb-8">{{ $data['heading'] }}</h2>@endif
        <div @class(['rounded-card bg-ink p-3 sm:p-4' => ($data['style'] ?? 'light') === 'dark'])>
            @include('galleries.grid', ['items' => $items->all(), 'layout' => $data['layout'] ?? 'grid'])
        </div>
    @endif
@endif
