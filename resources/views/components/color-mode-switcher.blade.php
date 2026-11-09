@props(['variant' => 'menu', 'dark' => false])
@php
    $modes = [
        'light' => ['label' => 'Light', 'icon' => 'heroicon-o-sun'],
        'dark' => ['label' => 'Dark', 'icon' => 'heroicon-o-moon'],
        'system' => ['label' => 'System', 'icon' => 'heroicon-o-computer-desktop'],
    ];
@endphp
@if (\App\Support\ColorMode::switchable())
    @if ($variant === 'menu')
        {{-- Header: icon button with a small menu --}}
        <div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false; $refs.button.focus()" x-on:click.outside="open = false">
            <button type="button" x-ref="button" x-on:click="open = !open" :aria-expanded="open" aria-haspopup="true"
                    class="inline-flex size-10 items-center justify-center rounded-full transition {{ $dark ? 'text-white/80 hover:bg-white/10' : 'text-muted hover:bg-ink/5 hover:text-ink' }}"
                    :aria-label="'Display: ' + $store.colorMode.mode + ' mode'" title="Light, dark or system display">
                <x-heroicon-o-sun class="size-5 dark:hidden" />
                <x-heroicon-o-moon class="hidden size-5 dark:block" />
            </button>
            <div x-cloak x-show="open" x-transition.origin.top.right.duration.150ms role="menu" aria-label="Display mode"
                 class="card absolute right-0 top-full z-50 mt-2 w-44 p-1.5 text-fg shadow-[var(--shadow-pop)]">
                @foreach ($modes as $key => $mode)
                    <button type="button" role="menuitemradio" :aria-checked="$store.colorMode.mode === '{{ $key }}'"
                            x-on:click="$store.colorMode.set('{{ $key }}'); open = false"
                            class="flex w-full items-center gap-3 rounded-ui px-3 py-2 text-left text-sm transition hover:bg-ink/5"
                            :class="$store.colorMode.mode === '{{ $key }}' && 'font-semibold text-ink'">
                        <x-dynamic-component :component="$mode['icon']" class="size-4.5 text-muted" />
                        <span class="flex-1">{{ $mode['label'] }}</span>
                        <x-heroicon-m-check class="size-4 text-accent-ink" x-show="$store.colorMode.mode === '{{ $key }}'" />
                    </button>
                @endforeach
            </div>
        </div>
    @else
        {{-- Segmented control (mobile menu, profile) --}}
        <div {{ $attributes->merge(['class' => 'grid grid-cols-3 gap-1 rounded-ui bg-subtle p-1 text-sm']) }} role="radiogroup" aria-label="Display mode">
            @foreach ($modes as $key => $mode)
                <button type="button" role="radio" :aria-checked="($store.colorMode.mode === '{{ $key }}').toString()"
                        x-on:click="$store.colorMode.set('{{ $key }}')"
                        class="flex items-center justify-center gap-1.5 rounded-[calc(var(--radius-ui)-2px)] px-2 py-2 font-medium transition"
                        :class="$store.colorMode.mode === '{{ $key }}' ? 'bg-surface text-ink shadow-sm ring-1 ring-line' : 'text-muted hover:text-ink'">
                    <x-dynamic-component :component="$mode['icon']" class="size-4" /> {{ $mode['label'] }}
                </button>
            @endforeach
        </div>
    @endif
@endif
