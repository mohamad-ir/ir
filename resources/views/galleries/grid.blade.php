{{-- Photo grid + full-screen viewer. $items: GalleryItem collection (or arrays with media/caption), $layout, $dark --}}
@php
    $layout = $layout ?? 'justified';
    $slides = collect($items)->map(function ($item) {
        $media = is_array($item) ? $item['media'] : $item->media;
        $caption = is_array($item) ? ($item['caption'] ?? $media->caption) : $item->captionText();

        return [
            'src' => $media->url(isset($media->variants['lg']) ? 'lg' : null),
            'full' => $media->url(),
            'srcset' => $media->srcset(),
            'thumb' => $media->thumbUrl(), 'w' => $media->width ?: 1600, 'h' => $media->height ?: 1000,
            'alt' => $media->altText(), 'caption' => $caption, 'credit' => $media->credit,
            'media' => $media,
        ];
    })->values();
@endphp
<div x-data="galleryViewer(@js($slides->map(fn ($s) => collect($s)->except('media'))->all()), @js($deepLink ?? false))" x-on:keydown.window="key($event)">
    @if ($layout === 'grid')
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($slides as $i => $s)
                <button type="button" x-on:click="open({{ $i }})" class="group relative aspect-square overflow-hidden rounded-ui bg-ink focus-visible:outline-2 focus-visible:outline-accent" aria-label="Open image {{ $i + 1 }}: {{ $s['alt'] }}">
                    <x-media-img :media="$s['media']" variant="md" sizes="(min-width: 1024px) 25vw, 50vw" class="size-full object-cover transition duration-500 group-hover:scale-[1.04]" />
                </button>
            @endforeach
        </div>
    @elseif ($layout === 'masonry')
        <div class="columns-2 gap-2 sm:columns-3 lg:columns-4 [&>*]:mb-2">
            @foreach ($slides as $i => $s)
                <button type="button" x-on:click="open({{ $i }})" class="group relative block w-full break-inside-avoid overflow-hidden rounded-ui bg-ink focus-visible:outline-2 focus-visible:outline-accent" aria-label="Open image {{ $i + 1 }}: {{ $s['alt'] }}">
                    <x-media-img :media="$s['media']" variant="md" sizes="(min-width: 1024px) 25vw, 50vw" class="w-full transition duration-500 group-hover:scale-[1.03]" />
                    @if ($s['caption'])<span class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2 pt-8 text-left text-xs text-white opacity-0 transition group-hover:opacity-100">{{ \Illuminate\Support\Str::limit($s['caption'], 90) }}</span>@endif
                </button>
            @endforeach
        </div>
    @else
        {{-- Justified rows: every image keeps its proportions and rows fill the width. --}}
        <div class="flex flex-wrap gap-2 after:flex-[999_1_0%] after:content-['']">
            @foreach ($slides as $i => $s)
                @php $ratio = max(0.4, min(3, $s['w'] / max(1, $s['h']))); @endphp
                <button type="button" x-on:click="open({{ $i }})" style="flex-grow: {{ round($ratio * 100) }}; flex-basis: {{ round($ratio * 210) }}px"
                        class="group relative overflow-hidden rounded-ui bg-ink focus-visible:outline-2 focus-visible:outline-accent" aria-label="Open image {{ $i + 1 }}: {{ $s['alt'] }}">
                    <span class="block" style="padding-bottom: {{ round(100 / $ratio, 3) }}%"></span>
                    <x-media-img :media="$s['media']" variant="md" sizes="(min-width: 1024px) 33vw, 60vw" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                    @if ($s['caption'])<span class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2 pt-8 text-left text-xs text-white opacity-0 transition group-hover:opacity-100">{{ \Illuminate\Support\Str::limit($s['caption'], 90) }}</span>@endif
                </button>
            @endforeach
        </div>
    @endif

    {{-- Full-screen viewer --}}
    <template x-teleport="body">
        <div x-show="current !== null" x-cloak x-transition.opacity.duration.200ms
             class="fixed inset-0 z-[100] flex flex-col bg-[#0a0c0f] text-white" role="dialog" aria-modal="true" aria-label="Image viewer"
             x-on:touchstart.passive="touchStart($event)" x-on:touchend.passive="touchEnd($event)">
            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                <span class="tabular-nums text-white/70" x-text="(current + 1) + ' / ' + slides.length"></span>
                <div class="flex items-center gap-1">
                    <button type="button" x-on:click="zoomed = !zoomed" class="rounded-ui p-2 text-white/80 hover:bg-white/10 hover:text-white" :aria-label="zoomed ? 'Fit to screen' : 'Zoom in'">
                        <x-heroicon-o-magnifying-glass-plus class="size-5" x-show="!zoomed" /><x-heroicon-o-magnifying-glass-minus class="size-5" x-show="zoomed" x-cloak />
                    </button>
                    <a :href="slide && slide.full" target="_blank" rel="noopener" class="rounded-ui p-2 text-white/80 hover:bg-white/10 hover:text-white" aria-label="Open original"><x-heroicon-o-arrows-pointing-out class="size-5" /></a>
                    <button type="button" x-on:click="close()" x-ref="closeButton" class="rounded-ui p-2 text-white/80 hover:bg-white/10 hover:text-white" aria-label="Close viewer"><x-heroicon-o-x-mark class="size-6" /></button>
                </div>
            </div>
            <div class="relative flex min-h-0 flex-1 items-center justify-center" :class="zoomed ? 'overflow-auto' : 'overflow-hidden'">
                <template x-if="slide">
                    <img :src="slide.src" :srcset="zoomed ? '' : slide.srcset" sizes="100vw" :alt="slide.alt" x-on:click="zoomed = !zoomed"
                         :class="zoomed ? 'max-w-none cursor-zoom-out' : 'max-h-full max-w-full cursor-zoom-in object-contain'" class="select-none" draggable="false">
                </template>
                <button type="button" x-show="slides.length > 1" x-on:click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 hover:bg-white/20 sm:left-4" aria-label="Previous image"><x-heroicon-m-chevron-left class="size-6" /></button>
                <button type="button" x-show="slides.length > 1" x-on:click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 hover:bg-white/20 sm:right-4" aria-label="Next image"><x-heroicon-m-chevron-right class="size-6" /></button>
            </div>
            <div class="mx-auto w-full max-w-4xl px-4 py-3 text-center" x-show="slide && (slide.caption || slide.credit)">
                <p class="text-[0.95rem] leading-relaxed text-white/90" x-text="slide && slide.caption"></p>
                <p class="mt-1 text-xs text-white/50" x-show="slide && slide.credit" x-text="slide && ('Credit: ' + slide.credit)"></p>
            </div>
            <div class="hidden gap-1.5 overflow-x-auto px-4 pb-4 sm:flex sm:justify-center" x-show="slides.length > 1">
                <template x-for="(s, i) in slides" :key="i">
                    <button type="button" x-on:click="go(i)" class="h-14 w-20 shrink-0 overflow-hidden rounded transition" :class="i === current ? 'ring-2 ring-white opacity-100' : 'opacity-50 hover:opacity-90'" :aria-label="'Image ' + (i + 1)">
                        <img :src="s.thumb" alt="" class="size-full object-cover" loading="lazy">
                    </button>
                </template>
            </div>
        </div>
    </template>
</div>
