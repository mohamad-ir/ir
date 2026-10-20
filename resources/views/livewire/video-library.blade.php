<div>
    @if ($featured)
        <a href="{{ $featured->urlPath() }}" class="group mb-14 grid overflow-hidden rounded-card bg-ink text-ink-contrast shadow-[var(--shadow-lift)] lg:grid-cols-[1.4fr_1fr]">
            <div class="relative aspect-video bg-black lg:aspect-auto">
                @if ($featured->thumbnail())<img src="{{ $featured->thumbnail() }}" alt="" class="size-full object-cover opacity-85 transition duration-700 group-hover:scale-[1.02] group-hover:opacity-100">@endif
                <span class="absolute inset-0 grid place-items-center"><span class="grid size-20 place-items-center rounded-full bg-white/90 text-ink shadow-2xl ring-[12px] ring-white/15 transition group-hover:scale-105"><x-heroicon-s-play class="ml-1 size-8" /></span></span>
            </div>
            <div class="flex flex-col justify-center p-8 lg:p-10">
                <p class="eyebrow !text-white/60">Featured · {{ $featured->category?->name }}</p>
                <h2 class="mt-3 font-heading text-3xl font-medium leading-tight text-white">{{ $featured->title }}</h2>
                @if ($featured->summary)<p class="mt-4 text-white/70">{{ $featured->summary }}</p>@endif
                <p class="mt-6 text-sm text-white/55">{{ $featured->durationLabel() }} @if($featured->level) · {{ $featured->level->getLabel() }} @endif</p>
            </div>
        </a>
    @endif

    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 scrollbar-none sm:mx-0 sm:flex-wrap sm:px-0">
            <button type="button" wire:click="$set('category', '')" class="filter-chip shrink-0" aria-pressed="{{ $category === '' ? 'true' : 'false' }}">All videos</button>
            @foreach ($categories as $c)
                <button type="button" wire:click="$set('category', '{{ $c->slug }}')" class="filter-chip shrink-0" aria-pressed="{{ $category === $c->slug ? 'true' : 'false' }}">{{ $c->name }} <span class="opacity-60">{{ $c->videos_count }}</span></button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <div class="relative flex-1 lg:w-64">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
                <input type="search" wire:model.live.debounce.350ms="search" class="input !py-2 !pl-9 text-sm" placeholder="Search videos & transcripts" aria-label="Search videos">
            </div>
            @if ($topics->isNotEmpty())
                <select wire:model.live="topic" class="input !w-auto !py-2 text-sm" aria-label="Topic">
                    <option value="">All topics</option>
                    @foreach ($topics as $t)<option value="{{ $t->slug }}">{{ $t->name }}</option>@endforeach
                </select>
            @endif
        </div>
    </div>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        @if ($videos->isEmpty())
            <x-empty-state icon="heroicon-o-play-circle" title="No videos found" text="Try another category or search term." />
        @else
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($videos as $video)
                    <x-video-card :video="$video" wire:key="video-{{ $video->id }}" />
                @endforeach
            </div>
        @endif
    </div>
    <div class="mt-12">{{ $videos->links() }}</div>
</div>
