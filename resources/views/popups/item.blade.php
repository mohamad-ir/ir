{{--
    One popup, slide-in or announcement bar (App\Models\Popup). Hidden until resources/js/popups.js decides
    to show it. Content is escaped; the body is rich text cleaned by RichText; links are restricted by Popup::href().
--}}
@php
    $id = 'popup-'.($popup->id ?: 'preview');
    $type = $popup->type;
    $scheme = $popup->color_scheme;
    $onColour = $popup->onColour();
    $href = $popup->href();
    $isBar = $type === 'bar';
    $hasImage = $popup->media && ! $isBar;
    $imageStyle = $hasImage ? $popup->image_style : null;
    $surface = match ($scheme) {
        'brand' => 'tone-ink',
        'accent' => 'bg-accent text-accent-contrast',
        'highlight' => 'bg-highlight text-highlight-contrast',
        'custom' => 'bg-[var(--popup-bg)] text-[var(--popup-fg)]',
        default => 'bg-surface text-fg ring-1 ring-line',
    };
    $titleColour = $scheme === 'default' && $imageStyle !== 'background' ? 'text-ink' : '';
    $button = match (true) {
        $scheme === 'custom' => 'btn bg-[var(--popup-btn)] text-[var(--popup-btn-fg)] shadow-sm hocus:brightness-110',
        $onColour => 'btn btn-light',
        $scheme === 'highlight' => 'btn btn-primary',
        default => 'btn btn-primary',
    };
    $closeTone = $onColour || $scheme === 'highlight' || $scheme === 'custom' ? 'hover:bg-black/10' : 'text-muted hover:bg-ink/5';
    $config = app(\App\Services\Popups\PopupService::class)->clientConfig($popup);
    $width = $type === 'modal'
        ? ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-lg', 'lg' => ($imageStyle === 'side' ? 'sm:max-w-3xl' : 'sm:max-w-2xl')][$popup->size] ?? 'sm:max-w-lg'
        : ['sm' => 'sm:max-w-xs', 'md' => 'sm:max-w-sm', 'lg' => 'sm:max-w-md'][$popup->size] ?? 'sm:max-w-sm';
    $anim = $popup->animation;
    $enterStart = match ($anim) {
        'zoom' => 'opacity-0 scale-95',
        'slide' => $isBar && $popup->position === 'top' ? 'opacity-0 -translate-y-full' : 'opacity-0 translate-y-6',
        'fade' => 'opacity-0',
        default => '',
    };
    $transition = $anim === 'none' ? '' : 'x-transition:enter="transition duration-300 ease-out motion-reduce:transition-none" x-transition:enter-start="'.$enterStart.'" x-transition:leave="transition duration-150 ease-in motion-reduce:transition-none" x-transition:leave-end="'.$enterStart.'"';
    $body = filled($popup->body) ? \App\Support\RichText::render($popup->body) : null;
@endphp

@if ($isBar)
    {{-- Announcement bar: at the top in the page flow, or fixed to the bottom of the screen --}}
    <div x-data="oaPopup({{ \Illuminate\Support\Js::from($config) }})" @unless ($config['initial']) x-cloak @endunless x-show="open" {!! $transition !!}
         x-on:mouseenter="hovering = true" x-on:mouseleave="hovering = false"
         id="{{ $id }}" role="region" aria-label="{{ $popup->title ?: 'Announcement' }}" data-popup-type="bar"
         @if ($popup->position === 'bottom') data-floating-bottom @endif
         style="{{ $popup->customStyle() }}{{ $popup->position === 'bottom' ? 'padding-bottom: env(safe-area-inset-bottom, 0px);' : '' }}"
         @class([$surface, 'text-sm', 'relative' => $popup->position !== 'bottom', 'fixed inset-x-0 bottom-0 z-40 shadow-[var(--shadow-pop)]' => $popup->position === 'bottom', '!ring-0 border-b border-line' => $scheme === 'default'])>
        <div class="container-site flex items-center gap-3 py-2.5 {{ $popup->show_close ? 'pr-10 sm:pr-12' : '' }}">
            <div class="flex min-w-0 flex-1 flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center">
                @if ($popup->eyebrow)
                    <span class="rounded-full bg-current/10 px-2 py-0.5 text-[0.7rem] font-semibold uppercase tracking-wider">{{ $popup->eyebrow }}</span>
                @endif
                <span class="font-medium">{{ $popup->title }}</span>
                @if ($href && $popup->cta_label)
                    <a href="{{ $href }}" x-on:click="goal('click', $event)" @if ($popup->cta_new_tab) target="_blank" rel="noopener" @endif
                       class="inline-flex items-center gap-1 font-semibold underline underline-offset-4 hover:no-underline">{{ $popup->cta_label }} <span aria-hidden="true">→</span></a>
                @endif
            </div>
            @if ($popup->show_close)
                <button type="button" x-on:click="close()" class="absolute right-2 top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-full {{ $closeTone }} sm:right-4" aria-label="Close announcement">
                    <x-heroicon-m-x-mark class="size-4.5" aria-hidden="true" />
                </button>
            @endif
        </div>
    </div>
@else
    @php
        $modal = $type === 'modal';
        $sheet = $modal && $popup->position === 'bottom';
    @endphp
    <div x-data="oaPopup({{ \Illuminate\Support\Js::from($config) }})" x-cloak x-show="open" id="{{ $id }}" data-popup-type="{{ $type }}"
         x-on:newsletter-subscribed.window="subscribed($event)"
         @if ($modal)
             x-on:keydown.escape.window="open && close('esc')"
             class="fixed inset-0 z-[55] flex {{ $sheet ? 'items-end sm:items-center' : 'items-center' }} justify-center p-3 sm:p-6 {{ $popup->show_overlay ? '' : 'pointer-events-none' }}"
             role="dialog" aria-modal="true"
         @else
             x-on:keydown.escape="close('esc')" data-floating-bottom
             class="fixed bottom-3 z-40 w-[calc(100%-1.5rem)] {{ $width }} {{ $popup->position === 'bottom_left' ? 'left-3 sm:left-6 sm:bottom-6' : 'right-3 sm:right-6 sm:bottom-6' }}"
             style="margin-bottom: calc(env(safe-area-inset-bottom, 0px) + var(--oa-bottom-bar, 0px))"
             role="dialog" aria-modal="false"
         @endif
         @if ($popup->title) aria-labelledby="{{ $id }}-title" @else aria-label="{{ $popup->name ?: 'Message' }}" @endif
         @if ($body) aria-describedby="{{ $id }}-text" @endif>

        @if ($modal && $popup->show_overlay)
            <div class="absolute inset-0 bg-black/55 backdrop-blur-[2px]" x-show="open" x-transition.opacity.duration.200ms x-on:click="overlayClick()" aria-hidden="true"></div>
        @endif

        <div x-show="open" {!! $transition !!} @if ($modal) x-trap.inert.noscroll="open" @endif
             x-on:mouseenter="hovering = true" x-on:mouseleave="hovering = false" x-on:focusin="hovering = true" x-on:focusout="hovering = false"
             style="{{ $popup->customStyle() }}"
             @class([
                 $surface, 'pointer-events-auto relative w-full overflow-hidden rounded-card shadow-[var(--shadow-pop)]',
                 $width,
                 'max-h-[calc(100dvh-1.5rem)] overflow-y-auto sm:max-h-[calc(100dvh-3rem)]' => $modal,
                 'sm:grid sm:grid-cols-[2fr_3fr]' => $imageStyle === 'side',
                 'text-white' => $imageStyle === 'background',
             ])>

            @if ($popup->show_close)
                <button type="button" x-on:click="close()" aria-label="Close"
                        @class(['absolute right-2.5 top-2.5 z-10 grid size-9 place-items-center rounded-full transition', $closeTone => ! $imageStyle, 'bg-black/45 text-white hover:bg-black/65' => (bool) $imageStyle])>
                    <x-heroicon-m-x-mark class="size-5" aria-hidden="true" />
                </button>
            @endif

            @if ($imageStyle === 'background')
                <x-media-img :media="$popup->media" variant="lg" sizes="(min-width: 640px) 640px, 100vw" alt="" class="absolute inset-0 size-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/60 to-black/30" aria-hidden="true"></div>
            @elseif ($imageStyle === 'side')
                <div class="relative aspect-[16/9] sm:aspect-auto sm:min-h-full">
                    <x-media-img :media="$popup->media" variant="md" sizes="(min-width: 640px) 320px, 100vw" class="absolute inset-0 size-full object-cover" />
                </div>
            @elseif ($imageStyle === 'top')
                <x-media-img :media="$popup->media" variant="md" sizes="(min-width: 640px) 520px, 100vw" class="aspect-[16/9] w-full object-cover" />
            @endif

            <div @class(['relative', 'p-5 sm:p-7' => $modal, 'p-4 sm:p-5' => ! $modal, 'flex min-h-72 flex-col justify-end' => $imageStyle === 'background'])>
                @if ($popup->preview ?? false)
                    <p class="mb-3 inline-flex rounded-full bg-amber-400 px-2 py-0.5 text-[0.7rem] font-bold uppercase tracking-wider text-black">Preview</p>
                @endif
                @if ($popup->eyebrow)
                    <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.14em] {{ $scheme === 'default' && ! $imageStyle ? 'text-accent-ink' : 'opacity-80' }}">{{ $popup->eyebrow }}</p>
                @endif
                @if ($popup->title)
                    <h2 id="{{ $id }}-title" @class(['font-heading font-semibold leading-tight text-balance', $titleColour, 'text-2xl sm:text-[1.7rem]' => $modal && $popup->size !== 'sm', 'text-xl' => ! $modal || $popup->size === 'sm', 'pr-8' => $popup->show_close])>{{ $popup->title }}</h2>
                @endif
                @if ($body)
                    <div id="{{ $id }}-text" class="mt-2 text-[0.95rem] leading-relaxed {{ $scheme === 'default' && $imageStyle !== 'background' ? 'text-muted' : 'opacity-90' }} [&_a]:font-medium [&_a]:underline [&_a]:underline-offset-2 [&_li]:mt-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p+p]:mt-2 [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5">{{ $body }}</div>
                @endif

                @if ($popup->form === 'newsletter')
                    <div class="mt-4">
                        <livewire:newsletter-form variant="footer" :compact="true" :dark="$onColour" :source="'popup-'.($popup->id ?: 'preview')" :key="'nl-'.$id" />
                    </div>
                @endif

                @if (($href && $popup->cta_label) || $popup->dismiss_label)
                    <div class="mt-5 flex flex-wrap items-center gap-2 {{ $modal ? 'flex-col-reverse sm:flex-row sm:justify-end' : '' }}">
                        @if ($popup->dismiss_label)
                            <button type="button" x-on:click="close('dismiss')" class="btn btn-sm {{ $onColour || $scheme !== 'default' ? 'hover:bg-black/10' : 'btn-ghost' }} {{ $modal ? 'w-full sm:w-auto' : '' }}">{{ $popup->dismiss_label }}</button>
                        @endif
                        @if ($href && $popup->cta_label)
                            <a href="{{ $href }}" x-on:click="goal('click', $event)" @if ($popup->cta_new_tab) target="_blank" rel="noopener" @endif
                               class="{{ $button }} {{ $modal ? 'w-full sm:w-auto' : 'btn-sm' }}">{{ $popup->cta_label }}</a>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>
@endif
