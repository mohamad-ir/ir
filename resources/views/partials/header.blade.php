@php
    $nav = $menus['header']['items'] ?? [];
    $ctas = $menus['header_cta']['items'] ?? [];
    $dark = ($headerStyle ?? 'light') === 'dark';
    $sticky = setting('theme.header_sticky', true);
@endphp
<header x-data="{ mobile: false, scrolled: false }"
        x-init="scrolled = window.scrollY > 8"
        x-on:scroll.window.passive="scrolled = window.scrollY > 8"
        x-on:keydown.escape.window="mobile = false"
        @class([
            'z-40 transition-[background-color,box-shadow,border-color] duration-300',
            'sticky top-0' => $sticky,
            'bg-ink text-ink-contrast' => $dark,
            'bg-paper/85 backdrop-blur-xl supports-[backdrop-filter]:bg-paper/75' => ! $dark,
        ])
        :class="scrolled ? '{{ $dark ? 'shadow-lg shadow-black/10' : 'shadow-[0_1px_0_var(--color-line),0_8px_24px_-12px_rgb(16_24_40/.12)]' }}' : '{{ $dark ? '' : 'shadow-[0_1px_0_var(--color-line)]' }}'">
    <div class="container-site flex h-[4.25rem] items-center gap-6">
        <x-logo :light="$dark" />

        <nav class="ml-4 hidden items-center gap-0.5 lg:flex" aria-label="Main">
            @foreach ($nav as $item)
                @if ($item['children'])
                    <div class="relative" x-data="{ open: false }" x-on:mouseenter="open = true" x-on:mouseleave="open = false" x-on:focusin="open = true" x-on:focusout="if (!$el.contains($event.relatedTarget)) open = false">
                        <button type="button" class="inline-flex items-center gap-1 whitespace-nowrap rounded-ui px-3 py-2 text-[0.9375rem] font-medium transition {{ $dark ? 'text-white/85 hover:text-white hover:bg-white/10' : ($item['active'] ? 'text-ink' : 'text-fg/80 hover:text-ink hover:bg-ink/5') }}" :aria-expanded="open">
                            {{ $item['label'] }}
                            <x-heroicon-m-chevron-down class="size-4 opacity-60 transition" ::class="open && 'rotate-180'" />
                        </button>
                        <div x-cloak x-show="open" x-transition.origin.top.duration.150ms class="absolute left-0 top-full z-50 w-80 pt-2">
                            <div class="card p-2 shadow-[var(--shadow-pop)] text-fg">
                                @foreach ($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" @if($child['new_tab']) target="_blank" rel="noopener" @endif class="block rounded-ui px-3 py-2.5 transition hover:bg-ink/5 {{ $child['active'] ? 'bg-ink/5' : '' }}">
                                        <span class="block text-sm font-semibold text-ink">{{ $child['label'] }}</span>
                                        @if ($child['description'])
                                            <span class="mt-0.5 block text-xs leading-snug text-muted">{{ $child['description'] }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener" @endif
                       @if($item['active']) aria-current="page" @endif
                       class="relative whitespace-nowrap rounded-ui px-3 py-2 text-[0.9375rem] font-medium transition {{ $dark ? 'text-white/85 hover:text-white hover:bg-white/10' : ($item['active'] ? 'text-ink' : 'text-fg/80 hover:text-ink hover:bg-ink/5') }}">
                        {{ $item['label'] }}
                        @if ($item['active'])
                            <span class="absolute inset-x-3 -bottom-[0.95rem] h-0.5 rounded-full bg-accent"></span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-1.5">
            @if (setting('header.show_search', true))
                <button type="button" x-on:click="$dispatch('open-search')" class="group inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm transition {{ $dark ? 'text-white/80 hover:bg-white/10' : 'text-muted ring-1 ring-line hover:ring-line-strong hover:text-fg bg-surface/70' }}" aria-label="Search">
                    <x-heroicon-o-magnifying-glass class="size-[1.15rem]" />
                    <span class="hidden 2xl:inline whitespace-nowrap pr-6">Search cases, topics…</span>
                    <kbd class="hidden 2xl:inline rounded border border-line px-1.5 text-[0.7rem] font-sans text-muted">/</kbd>
                </button>
            @endif

            @if (setting('header.show_account', true))
                @auth
                    <a href="{{ route('account.dashboard') }}" class="hidden sm:inline-flex items-center gap-2 rounded-full p-1 pr-3 text-sm font-medium transition {{ $dark ? 'hover:bg-white/10' : 'hover:bg-ink/5' }}">
                        <span class="grid size-8 place-items-center rounded-full bg-accent text-xs font-bold text-accent-contrast">{{ auth()->user()->initials() }}</span>
                        <span class="hidden md:inline">My learning</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex btn btn-sm {{ $dark ? 'btn-outline-light' : 'btn-ghost' }}">Sign in</a>
                @endauth
            @endif

            @foreach ($ctas as $cta)
                <a href="{{ $cta['url'] }}" class="hidden md:inline-flex btn btn-sm {{ $dark ? 'btn-light' : 'btn-primary' }}">{{ $cta['label'] }}</a>
            @endforeach

            <button type="button" class="lg:hidden inline-flex size-10 items-center justify-center rounded-ui {{ $dark ? 'hover:bg-white/10' : 'hover:bg-ink/5' }}" x-on:click="mobile = true" aria-label="Open menu" :aria-expanded="mobile">
                <x-heroicon-o-bars-3 class="size-6" />
            </button>
        </div>
    </div>

    {{-- Mobile navigation --}}
    <template x-teleport="body">
        <div x-cloak x-show="mobile" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
            <div x-show="mobile" x-transition.opacity class="absolute inset-0 bg-ink/40 backdrop-blur-sm" x-on:click="mobile = false"></div>
            <div x-show="mobile" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full"
                 class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-surface shadow-2xl">
                <div class="flex h-[4.25rem] items-center justify-between border-b border-line px-5">
                    <x-logo />
                    <button type="button" class="inline-flex size-10 items-center justify-center rounded-ui hover:bg-ink/5" x-on:click="mobile = false" aria-label="Close menu">
                        <x-heroicon-o-x-mark class="size-6" />
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Mobile">
                    <button type="button" x-on:click="mobile = false; $dispatch('open-search')" class="mb-3 flex w-full items-center gap-3 rounded-ui bg-subtle px-4 py-3 text-left text-muted">
                        <x-heroicon-o-magnifying-glass class="size-5" /> Search cases, articles, videos…
                    </button>
                    @foreach ($nav as $item)
                        <div x-data="{ open: {{ $item['active'] ? 'true' : 'false' }} }">
                            @if ($item['children'])
                                <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between rounded-ui px-4 py-3 text-base font-semibold text-ink hover:bg-ink/5">
                                    {{ $item['label'] }} <x-heroicon-m-chevron-down class="size-5 transition" ::class="open && 'rotate-180'" />
                                </button>
                                <div x-show="open" x-collapse class="ml-4 border-l border-line pl-2">
                                    @foreach ($item['children'] as $child)
                                        <a href="{{ $child['url'] }}" class="block rounded-ui px-4 py-2.5 text-[0.9375rem] text-fg/85 hover:bg-ink/5">{{ $child['label'] }}</a>
                                    @endforeach
                                </div>
                            @else
                                <a href="{{ $item['url'] }}" class="block rounded-ui px-4 py-3 text-base font-semibold {{ $item['active'] ? 'bg-ink/5 text-ink' : 'text-ink hover:bg-ink/5' }}">{{ $item['label'] }}</a>
                            @endif
                        </div>
                    @endforeach
                </nav>
                <div class="space-y-2 border-t border-line p-5">
                    @auth
                        <a href="{{ route('account.dashboard') }}" class="btn btn-secondary w-full">My learning</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-secondary w-full">Sign in</a>
                        @if (setting('site.registration_enabled', true))
                            <a href="{{ route('register') }}" class="btn btn-primary w-full">Create free account</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </template>
</header>
