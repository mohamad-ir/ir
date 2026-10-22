{{-- Image banner / slider. Pass :banner, or placement="cases" to load the slider assigned to that place. --}}
@props(['banner' => null, 'placement' => null, 'contained' => true])
@php
    $banner ??= $placement ? \App\Models\Banner::forPlacement($placement) : null;
    $slides = $banner?->liveSlides() ?? collect();
    $height = match ($banner?->height) { 'compact' => 'h-60 sm:h-56 lg:h-64', 'tall' => 'h-[30rem] sm:h-[30rem] lg:h-[36rem]', default => 'h-[23rem] sm:h-80 lg:h-[26rem]' };
@endphp
@if ($slides->isNotEmpty())
    <div {{ $attributes->merge(['class' => $contained ? 'container-site pt-6' : '']) }}>
        <section x-data="bannerSlider({ count: {{ $slides->count() }}, autoplay: @js($banner->autoplay), interval: {{ max(3, $banner->interval) }} })"
                 x-on:mouseenter="pause()" x-on:mouseleave="resume()" x-on:focusin="pause()" x-on:focusout="resume()"
                 x-on:keydown.left="prev()" x-on:keydown.right="next()" x-on:touchstart.passive="touchStart($event)" x-on:touchend.passive="touchEnd($event)"
                 class="group/slider relative isolate overflow-hidden rounded-card bg-ink {{ $height }}"
                 aria-roledescription="carousel" aria-label="{{ $banner->name }}" data-banner="{{ $banner->id }}">
            @foreach ($slides as $i => $slide)
                @php
                    $href = $slide->href();
                    $align = match ($slide->text_position) { 'center' => 'items-center text-center', 'right' => 'items-end text-right', default => 'items-start text-left' };
                    $overlay = match ($slide->overlay) { 'none' => '', 'light' => 'bg-gradient-to-r from-white/85 via-white/40 to-transparent', default => 'bg-gradient-to-r from-black/70 via-black/35 to-transparent' };
                    $light = $slide->overlay === 'light';
                @endphp
                <div x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif
                     @if ($banner->transition === 'fade')
                         x-transition:enter="transition-opacity duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition-opacity duration-700 absolute inset-0" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     @else
                         x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition duration-300 ease-in absolute inset-0" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     @endif
                     class="absolute inset-0" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $slides->count() }}">
                    <picture>
                        @if ($slide->mobileMedia)<source media="(max-width: 640px)" srcset="{{ $slide->mobileMedia->url(isset($slide->mobileMedia->variants['md']) ? 'md' : null) }}">@endif
                        <img src="{{ $slide->media->url(isset($slide->media->variants['lg']) ? 'lg' : null) }}" @if ($slide->media->srcset()) srcset="{{ $slide->media->srcset() }}" sizes="(min-width: 1280px) 1280px, 100vw" @endif
                             alt="{{ $slide->alt ?: ($slide->title ?: $slide->media->altText()) }}" class="absolute inset-0 size-full object-cover"
                             @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                    </picture>
                    @if ($overlay)<div class="absolute inset-0 {{ $overlay }}"></div>@endif
                    @if ($slide->title || $slide->text || $slide->button_label)
                        <div class="relative flex h-full flex-col justify-center gap-3 px-6 pb-10 pt-8 sm:px-12 sm:py-8 lg:px-16 {{ $align }}">
                            <div class="max-w-xl">
                                @if ($slide->eyebrow)<p class="text-xs font-semibold uppercase tracking-[.18em] {{ $light ? 'text-accent-ink' : 'text-white/75' }}">{{ $slide->eyebrow }}</p>@endif
                                @if ($slide->title)<h2 class="mt-2 font-heading text-[1.75rem] font-medium leading-tight sm:text-4xl lg:text-5xl {{ $light ? 'text-ink' : 'text-white' }}">{{ $slide->title }}</h2>@endif
                                @if ($slide->text)<p class="mt-3 line-clamp-3 text-[0.95rem] leading-relaxed sm:text-lg {{ $light ? 'text-fg/85' : 'text-white/85' }}">{{ $slide->text }}</p>@endif
                                @if ($href && $slide->button_label)
                                    <a href="{{ $href }}" data-banner-slide="{{ $slide->id }}" @if ($slide->new_tab) target="_blank" @endif @if ($slide->isExternal()) rel="noopener" @endif
                                       class="btn {{ $light ? 'btn-primary' : 'btn-light' }} mt-5 sm:mt-6">{{ $slide->button_label }} <x-heroicon-m-arrow-right /></a>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($href && ! $slide->button_label)
                        <a href="{{ $href }}" data-banner-slide="{{ $slide->id }}" class="absolute inset-0 z-[1]" @if ($slide->new_tab) target="_blank" @endif @if ($slide->isExternal()) rel="noopener" @endif aria-label="{{ $slide->title ?: $slide->alt ?: 'Open' }}"></a>
                    @endif
                </div>
            @endforeach

            @if ($slides->count() > 1)
                @if ($banner->show_arrows)
                    <button type="button" x-on:click="prev()" class="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/35 p-2.5 text-white opacity-0 backdrop-blur transition hover:bg-black/55 focus-visible:opacity-100 group-hover/slider:opacity-100" aria-label="Previous slide"><x-heroicon-m-chevron-left class="size-5" /></button>
                    <button type="button" x-on:click="next()" class="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/35 p-2.5 text-white opacity-0 backdrop-blur transition hover:bg-black/55 focus-visible:opacity-100 group-hover/slider:opacity-100" aria-label="Next slide"><x-heroicon-m-chevron-right class="size-5" /></button>
                @endif
                @if ($banner->show_dots)
                    <div class="absolute inset-x-0 bottom-3 z-10 flex justify-center gap-2">
                        @foreach ($slides as $i => $slide)
                            <button type="button" x-on:click="go({{ $i }})" class="h-1.5 rounded-full transition-all" :class="current === {{ $i }} ? 'w-6 bg-white' : 'w-1.5 bg-white/55 hover:bg-white/80'" aria-label="Show slide {{ $i + 1 }}" :aria-current="current === {{ $i }}"></button>
                        @endforeach
                    </div>
                @endif
                @if ($banner->autoplay)
                    <button type="button" x-on:click="toggle()" class="absolute right-3 top-3 z-10 rounded-full bg-black/35 p-1.5 text-white/85 opacity-0 backdrop-blur transition focus-visible:opacity-100 group-hover/slider:opacity-100" :aria-label="stopped ? 'Play slideshow' : 'Pause slideshow'">
                        <x-heroicon-m-pause class="size-4" x-show="!stopped" /><x-heroicon-m-play class="size-4" x-show="stopped" x-cloak />
                    </button>
                @endif
            @endif
        </section>
    </div>
@endif
