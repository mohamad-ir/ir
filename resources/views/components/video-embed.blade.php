{{-- Click-to-play video: nothing is loaded from YouTube/Vimeo until the visitor presses play. --}}
@props(['embed', 'caption' => null, 'dark' => false])
@if ($embed)
    <figure {{ $attributes->merge(['class' => 'video-embed not-prose my-8']) }}>
        <div x-data="{ playing: false }" class="viewer-frame relative aspect-video overflow-hidden">
            <template x-if="playing">
                <div class="absolute inset-0">
                    @if ($embed['kind'] === 'file')
                        <video src="{{ $embed['src'] }}" controls autoplay playsinline preload="metadata" class="size-full bg-black" @if($embed['poster']) poster="{{ $embed['poster'] }}" @endif></video>
                    @else
                        <iframe src="{{ $embed['src'] }}" title="{{ $embed['title'] }}" class="size-full" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    @endif
                </div>
            </template>
            <button type="button" x-show="!playing" x-on:click="playing = true" class="group absolute inset-0 block size-full" aria-label="Play video: {{ $embed['title'] }}">
                @if ($embed['poster'])
                    <img src="{{ $embed['poster'] }}" alt="" class="size-full object-cover opacity-85 transition duration-500 group-hover:opacity-100" loading="lazy">
                @endif
                <span class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent"></span>
                <span class="absolute inset-0 grid place-items-center">
                    <span class="grid size-16 place-items-center rounded-full bg-white/95 text-ink shadow-2xl ring-[12px] ring-white/15 transition duration-300 group-hover:scale-110"><x-heroicon-s-play class="ml-1 size-7" /></span>
                </span>
                <span class="absolute bottom-3 left-4 right-4 truncate text-left text-sm font-semibold text-white">{{ $embed['title'] }}</span>
                @if (in_array($embed['provider'], ['youtube', 'vimeo'], true))
                    <span class="absolute right-4 top-3 text-[0.7rem] text-white/60">{{ ucfirst($embed['provider']) }} · loads when played</span>
                @endif
            </button>
        </div>
        @if ($caption || $embed['library'])
            <figcaption class="mt-2 text-sm text-muted">
                {{ $caption }}
                @if ($embed['library'])<a href="{{ $embed['library']->urlPath() }}" class="link ml-1">Open in the video library</a>@endif
            </figcaption>
        @endif
    </figure>
@endif
