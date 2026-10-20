@php
    $provider = $video->provider?->value ?? 'youtube';
    $embed = $video->embedUrl();
@endphp
<div x-data="videoPlayer({ id: {{ $video->id }}, provider: @js($provider) })" @if(($startAt ?? 0) > 0) x-init="play({{ (int) $startAt }})" @endif x-on:seek-video.window="seek($event.detail)" class="viewer-frame aspect-video shadow-[var(--shadow-pop)]">
    @if ($embed)
        <template x-if="playing">
            <div class="absolute inset-0">
                @if ($video->isFilePlayer())
                    <video x-ref="video" src="{{ $embed }}" controls playsinline preload="metadata" class="size-full bg-black" @if($video->thumbnail()) poster="{{ $video->thumbnail() }}" @endif></video>
                @else
                    <iframe x-ref="frame" src="{{ $embed }}" title="{{ $video->title }}" class="size-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                @endif
            </div>
        </template>
        <button type="button" x-show="!playing" x-on:click="play()" class="group absolute inset-0 block size-full" aria-label="Play video: {{ $video->title }}">
            @if ($video->thumbnail())
                <img src="{{ $video->thumbnail() }}" alt="" class="size-full object-cover opacity-85 transition duration-500 group-hover:opacity-100" loading="lazy">
            @endif
            <span class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent"></span>
            <span class="absolute inset-0 grid place-items-center">
                <span class="grid size-20 place-items-center rounded-full bg-white/95 text-ink shadow-2xl ring-[14px] ring-white/15 transition duration-300 group-hover:scale-110"><x-heroicon-s-play class="ml-1 size-9" /></span>
            </span>
            @if ($video->durationLabel())
                <span class="absolute bottom-4 right-4 rounded bg-black/75 px-2 py-1 text-sm font-semibold tabular-nums text-white">{{ $video->durationLabel() }}</span>
            @endif
            @if (in_array($provider, ['youtube', 'vimeo']))
                <span class="absolute bottom-4 left-4 text-xs text-white/60">Plays from {{ ucfirst($provider) }} · privacy-enhanced mode</span>
            @endif
        </button>
    @else
        <div class="grid size-full place-items-center text-white/60">Video unavailable</div>
    @endif
</div>
