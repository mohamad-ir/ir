<a href="{{ $video->url() }}" class="not-prose my-8 flex items-center gap-4 rounded-card bg-ink p-3 pr-5 text-ink-contrast no-underline transition hover:bg-ink/95">
    <span class="relative aspect-video w-36 shrink-0 overflow-hidden rounded-ui bg-black">
        @if ($video->thumbnail())<img src="{{ $video->thumbnail() }}" alt="" class="size-full object-cover opacity-90" loading="lazy">@endif
        <span class="absolute inset-0 grid place-items-center"><span class="grid size-9 place-items-center rounded-full bg-white/90 text-ink">▶</span></span>
    </span>
    <span class="min-w-0">
        <span class="block text-xs font-semibold uppercase tracking-[.14em] opacity-60">Watch video{{ $video->durationLabel() ? ' · '.$video->durationLabel() : '' }}</span>
        <span class="mt-1 block font-heading text-lg font-semibold leading-snug">{{ $video->title }}</span>
    </span>
</a>
