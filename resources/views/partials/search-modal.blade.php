<div x-data="searchPalette('{{ route('search.suggest') }}', '{{ route('search') }}')" x-on:open-search.window="show()" x-on:keydown.escape.window="close()">
    <template x-teleport="body">
        <div x-cloak x-show="open" class="fixed inset-0 z-[60] overflow-y-auto p-4 pt-[12vh] sm:p-6 sm:pt-[14vh]" role="dialog" aria-modal="true" aria-label="Search">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-ink/45 backdrop-blur-sm" x-on:click="close()"></div>
            <div x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 scale-[.98] -translate-y-2" x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0 scale-[.98]"
                 class="relative mx-auto max-w-2xl overflow-hidden rounded-card bg-surface shadow-[var(--shadow-pop)] ring-1 ring-line">
                <form action="{{ route('search') }}" method="get" class="flex items-center gap-3 border-b border-line px-5" x-on:submit="if (active >= 0 && results[active]) { $event.preventDefault(); go(results[active].url) }">
                    <x-heroicon-o-magnifying-glass class="size-5 shrink-0 text-muted" />
                    <input x-ref="input" name="q" type="search" x-model="query" x-on:input.debounce.180ms="fetchResults()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                           class="h-16 w-full border-0 bg-transparent text-lg text-fg placeholder:text-muted/70 focus:ring-0 focus:outline-none" placeholder="Search e.g. CSR, VKH, macular hole…" autocomplete="off" aria-label="Search">
                    <span x-show="loading" class="size-4 animate-spin rounded-full border-2 border-accent border-t-transparent"></span>
                    <kbd class="hidden sm:block rounded border border-line px-1.5 py-0.5 text-xs text-muted">Esc</kbd>
                </form>

                <div class="max-h-[60vh] overflow-y-auto">
                    <template x-if="query.length < 2">
                        <div class="p-5">
                            <p class="text-xs font-semibold tracking-[.14em] text-muted uppercase">Popular topics</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach (\App\Models\Topic::where('is_featured', true)->orderBy('sort_order')->limit(10)->get() as $topic)
                                    <a href="{{ $topic->urlPath() }}" class="filter-chip">{{ $topic->abbreviation ?: $topic->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    </template>
                    <ul x-show="results.length" class="p-2" role="listbox">
                        <template x-for="(item, i) in results" :key="item.url">
                            <li role="option" :aria-selected="i === active">
                                <a :href="item.url" x-on:mouseenter="active = i" :class="i === active ? 'bg-ink/[.05]' : ''" class="flex items-center gap-4 rounded-ui px-3 py-2.5">
                                    <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-ui bg-viewer">
                                        <template x-if="item.image"><img :src="item.image" alt="" class="size-full object-cover"></template>
                                        <template x-if="!item.image"><span class="text-[0.65rem] font-bold uppercase tracking-wider text-white/70" x-text="item.typeShort"></span></template>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium text-fg" x-text="item.title"></span>
                                        <span class="block truncate text-sm text-muted" x-text="item.summary"></span>
                                    </span>
                                    <span class="chip shrink-0" x-text="item.type"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                    <template x-if="query.length >= 2 && !loading && results.length === 0 && searched">
                        <div class="px-6 py-10 text-center">
                            <p class="font-medium text-fg">No results for “<span x-text="query"></span>”</p>
                            <p class="mt-1 text-sm text-muted">Try a disease name, abbreviation or OCT sign.</p>
                        </div>
                    </template>
                </div>
                <div x-show="query.length >= 2" class="flex items-center justify-between border-t border-line bg-subtle px-5 py-3 text-sm">
                    <a :href="'{{ route('search') }}?q=' + encodeURIComponent(query)" class="link-arrow">See all results <x-heroicon-m-arrow-right /></a>
                    <span class="hidden text-muted sm:inline">↑↓ to navigate · ↵ to open</span>
                </div>
            </div>
        </div>
    </template>
</div>
