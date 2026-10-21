@php($video = $data['video'] ?? null)
@if ($video)
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 text-center">
            @if (! empty($data['eyebrow']))<p class="eyebrow mb-3">{{ $data['eyebrow'] }}</p>@endif
            <h2 class="h-section {{ $tone === 'ink' ? 'text-white' : '' }}">{{ ($data['heading'] ?? null) ?: $video->title }}</h2>
            @if (! empty($data['text']))<p class="mx-auto mt-4 max-w-2xl text-lg text-muted">{{ $data['text'] }}</p>@endif
        </div>
        @include('videos.partials.player', ['video' => $video])
        <p class="mt-4 text-center"><a href="{{ $video->urlPath() }}" class="link-arrow">Notes, chapters &amp; transcript <x-heroicon-m-arrow-right /></a></p>
    </div>
@endif
