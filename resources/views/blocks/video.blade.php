@php $video = $data['video'] ?? null; $embed = $data['embed'] ?? null; @endphp
@if ($video || $embed)
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 text-center">
            @if (! empty($data['eyebrow']))<p class="eyebrow mb-3">{{ $data['eyebrow'] }}</p>@endif
            @if (($data['heading'] ?? null) || $video)<h2 class="h-section {{ $tone === 'ink' ? 'text-white' : '' }}">{{ ($data['heading'] ?? null) ?: $video->title }}</h2>@endif
            @if (! empty($data['text']))<p class="mx-auto mt-4 max-w-2xl text-lg text-muted">{{ $data['text'] }}</p>@endif
        </div>
        @if ($video && ($data['locked'] ?? false))
            <div class="viewer-frame relative aspect-video">
                @if ($video->thumbnail())<img src="{{ $video->thumbnail() }}" alt="" class="size-full object-cover opacity-40">@endif
                <div class="absolute inset-0 grid place-items-center p-6"><x-locked-content :content="$video" thing="video" /></div>
            </div>
        @elseif ($video)
            @include('videos.partials.player', ['video' => $video])
            <p class="mt-4 text-center"><a href="{{ $video->urlPath() }}" class="link-arrow">Notes, chapters &amp; transcript <x-heroicon-m-arrow-right /></a></p>
        @else
            <x-video-embed :embed="$embed" class="!my-0" />
        @endif
    </div>
@elseif ($context['preview'] ?? false)
    <p class="rounded-ui bg-subtle p-6 text-center text-sm text-muted">Featured video — choose a video.</p>
@endif
