@props(['gallery'])
@php
    $cover = $gallery->cover();
    $thumbs = $gallery->relationLoaded('items') ? $gallery->items->filter(fn ($i) => $i->media && $i->media_id !== $cover?->id)->take(2) : collect();
    $count = $gallery->items_count ?? $gallery->items->count();
@endphp
<article {{ $attributes->merge(['class' => 'group card-interactive relative flex flex-col overflow-hidden']) }}>
    <div class="grid aspect-[4/3] grid-cols-3 grid-rows-2 gap-0.5 bg-ink">
        <div @class(['relative overflow-hidden', 'col-span-2 row-span-2' => $thumbs->count() === 2, 'col-span-3 row-span-2' => $thumbs->count() < 2])>
            @if ($cover)
                <x-media-img :media="$cover" variant="md" sizes="(min-width: 1024px) 400px, 100vw" class="size-full object-cover transition duration-700 group-hover:scale-[1.04]" />
            @else
                <div class="grid size-full place-items-center text-white/40"><x-heroicon-o-photo class="size-10" /></div>
            @endif
        </div>
        @if ($thumbs->count() === 2)
            @foreach ($thumbs as $thumb)
                <div class="relative overflow-hidden"><x-media-img :media="$thumb->media" variant="thumb" sizes="140px" class="size-full object-cover opacity-90 transition duration-700 group-hover:opacity-100" /></div>
            @endforeach
        @endif
    </div>
    <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/65 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur">
        <x-heroicon-m-photo class="size-3.5" /> {{ $count }}
    </span>
    <div class="flex flex-1 flex-col p-5">
        @if ($gallery->topics->isNotEmpty())
            <div class="mb-2 flex flex-wrap gap-1.5">@foreach ($gallery->topics->take(2) as $topic)<span class="chip chip-accent">{{ $topic->abbreviation ?: $topic->name }}</span>@endforeach</div>
        @endif
        <h3 class="font-heading text-xl font-semibold leading-snug text-ink">
            <a href="{{ $gallery->urlPath() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $gallery->title }}</a>
        </h3>
        @if ($gallery->summary)<p class="mt-2 line-clamp-2 text-[0.9375rem] text-muted">{{ $gallery->summary }}</p>@endif
        <span class="link-arrow mt-auto pt-4">View gallery <x-heroicon-m-arrow-right /></span>
    </div>
</article>
