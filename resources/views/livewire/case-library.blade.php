<div class="grid gap-10 lg:grid-cols-[17rem_1fr]" x-data="{ filters: false }">
    {{-- Filters --}}
    <aside class="lg:sticky lg:top-24 lg:self-start">
        <div class="flex items-center gap-2 lg:hidden">
            <button type="button" class="btn btn-secondary flex-1" x-on:click="filters = !filters" :aria-expanded="filters">
                <x-heroicon-o-adjustments-horizontal /> Filters @if($activeFilters)<span class="chip chip-accent">{{ $activeFilters }}</span>@endif
            </button>
        </div>
        <div class="mt-4 space-y-7 lg:mt-0 lg:!block" :class="filters ? 'block' : 'hidden'">
            <div>
                <label for="case-search" class="field-label">Search the library</label>
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
                    <input id="case-search" type="search" wire:model.live.debounce.350ms="search" class="input !pl-9" placeholder="Diagnosis, sign, topic…">
                </div>
            </div>

            <div>
                <p class="field-label">Disease / topic</p>
                <div class="max-h-[22rem] space-y-0.5 overflow-y-auto pr-1">
                    <button type="button" wire:click="$set('topic', '')" @class(['flex w-full items-center justify-between rounded-ui px-3 py-1.5 text-left text-sm transition', 'bg-ink text-ink-contrast font-semibold' => $topic === '', 'hover:bg-ink/5' => $topic !== ''])>All topics</button>
                    @foreach ($topics as $t)
                        <button type="button" wire:click="$set('topic', '{{ $t->slug }}')" @class(['flex w-full items-center justify-between gap-2 rounded-ui px-3 py-1.5 text-left text-sm transition', 'bg-ink text-ink-contrast font-semibold' => $topic === $t->slug, 'hover:bg-ink/5 font-medium' => $topic !== $t->slug])>
                            <span class="truncate">{{ $t->name }}</span><span class="text-xs opacity-60 tabular-nums">{{ $t->cases_count + $t->children->sum('cases_count') }}</span>
                        </button>
                        @foreach ($t->children as $child)
                            <button type="button" wire:click="$set('topic', '{{ $child->slug }}')" @class(['ml-3 flex w-[calc(100%-.75rem)] items-center justify-between gap-2 rounded-ui px-3 py-1.5 text-left text-sm transition', 'bg-ink text-ink-contrast font-semibold' => $topic === $child->slug, 'text-fg/80 hover:bg-ink/5' => $topic !== $child->slug])>
                                <span class="truncate">{{ $child->abbreviation ?: $child->name }}</span><span class="text-xs opacity-60 tabular-nums">{{ $child->cases_count }}</span>
                            </button>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div>
                <p class="field-label">Imaging</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($modalities as $m)
                        <button type="button" wire:click="$set('modality', '{{ $modality === $m->slug ? '' : $m->slug }}')" class="filter-chip !px-3 !py-1 !text-xs" aria-pressed="{{ $modality === $m->slug ? 'true' : 'false' }}">{{ $m->label() }}</button>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="field-label">Difficulty</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($difficulties as $d)
                        <button type="button" wire:click="$set('difficulty', '{{ $difficulty === $d->value ? '' : $d->value }}')" class="filter-chip !px-3 !py-1 !text-xs" aria-pressed="{{ $difficulty === $d->value ? 'true' : 'false' }}">{{ $d->getLabel() }}</button>
                    @endforeach
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-sm font-medium">
                <input type="checkbox" wire:model.live="cotw" class="checkbox"> Case of the Week archive only
            </label>

            @if ($activeFilters)
                <button type="button" wire:click="clearFilters" class="link text-sm">Clear all filters</button>
            @endif
        </div>
    </aside>

    {{-- Results --}}
    <div>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-muted" aria-live="polite">
                <span class="font-semibold text-fg">{{ number_format($cases->total()) }}</span> {{ \Illuminate\Support\Str::plural('case', $cases->total()) }}
                <span wire:loading class="ml-2 inline-block size-3.5 animate-spin rounded-full border-2 border-accent border-t-transparent align-middle"></span>
            </p>
            <div class="flex items-center gap-2">
                <label for="case-sort" class="sr-only">Sort</label>
                <select id="case-sort" wire:model.live="sort" class="input !w-auto !py-2 text-sm">
                    <option value="latest">Newest first</option>
                    <option value="popular">Most viewed</option>
                    <option value="oldest">Oldest first</option>
                    <option value="title">A–Z</option>
                </select>
                <div class="hidden rounded-ui p-0.5 ring-1 ring-line-strong sm:flex" role="group" aria-label="Layout">
                    <button type="button" wire:click="$set('view', 'grid')" class="rounded-[calc(var(--radius-ui)-2px)] p-1.5 {{ $view === 'grid' ? 'bg-ink text-ink-contrast' : 'text-muted' }}" aria-label="Grid view" aria-pressed="{{ $view === 'grid' ? 'true' : 'false' }}"><x-heroicon-m-squares-2x2 class="size-4.5" /></button>
                    <button type="button" wire:click="$set('view', 'list')" class="rounded-[calc(var(--radius-ui)-2px)] p-1.5 {{ $view === 'list' ? 'bg-ink text-ink-contrast' : 'text-muted' }}" aria-label="List view" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"><x-heroicon-m-bars-4 class="size-4.5" /></button>
                </div>
            </div>
        </div>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            @if ($cases->isEmpty())
                <x-empty-state title="No cases match these filters" text="Try removing a filter or searching for a broader term.">
                    <button type="button" wire:click="clearFilters" class="btn btn-secondary mt-6">Clear filters</button>
                </x-empty-state>
            @elseif ($view === 'list')
                <div class="divide-y divide-line">
                    @foreach ($cases as $case)
                        <x-case-card :case="$case" layout="list" wire:key="case-{{ $case->id }}" class="!mx-0 py-4" />
                    @endforeach
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($cases as $case)
                        <x-case-card :case="$case" wire:key="case-{{ $case->id }}" />
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-10">{{ $cases->onEachSide(1)->links() }}</div>
    </div>
</div>
