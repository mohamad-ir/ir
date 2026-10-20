@props(['video', 'layout' => 'card'])
<article {{ $attributes->merge(['class' => 'group relative flex flex-col '.($layout === 'list' ? 'sm:flex-row gap-5' : '')]) }}>
    <div class="viewer-frame aspect-video {{ $layout === 'list' ? 'sm:w-64 shrink-0' : '' }}">
        @if ($thumb = $video->thumbnail())
            <img src="{{ $thumb }}" alt="" loading="lazy" decoding="async" class="size-full object-cover opacity-90 transition duration-700 group-hover:scale-[1.04] group-hover:opacity-100">
        @endif
        <div class="absolute inset-0 grid place-items-center">
            <span class="grid size-14 place-items-center rounded-full bg-white/90 text-ink shadow-xl ring-8 ring-white/15 transition duration-300 group-hover:scale-110 group-hover:bg-white">
                <x-heroicon-s-play class="ml-0.5 size-6" />
            </span>
        </div>
        @if ($video->durationLabel())
            <span class="absolute bottom-2.5 right-2.5 rounded bg-black/75 px-1.5 py-0.5 text-xs font-semibold tabular-nums text-white">{{ $video->durationLabel() }}</span>
        @endif
    </div>
    <div class="{{ $layout === 'list' ? 'py-1' : 'pt-4' }}">
        <div class="flex items-center gap-2 text-xs">
            <span class="eyebrow">{{ $video->category?->name ?? 'Video' }}</span>
            @if ($video->access_level?->value === 'registered')
                <span class="chip chip-outline !text-[0.68rem]"><x-heroicon-m-lock-closed class="size-3" /> Members</span>
            @endif
        </div>
        <h3 class="mt-1.5 font-heading text-lg font-semibold leading-snug text-ink">
            <a href="{{ $video->urlPath() }}" class="after:absolute after:inset-0 focus:outline-none group-hover:text-accent-ink transition">{{ $video->title }}</a>
        </h3>
        @if ($video->summary)
            <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $video->summary }}</p>
        @endif
    </div>
</article>
