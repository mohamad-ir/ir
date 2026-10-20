<div>
    @if ($featured)
        <article class="group relative mb-14 grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="overflow-hidden rounded-card bg-subtle shadow-[var(--shadow-lift)]">
                @if ($featured->featuredMedia)
                    <x-media-img :media="$featured->featuredMedia" variant="lg" sizes="(min-width:1024px) 600px, 100vw" class="aspect-[16/10] w-full object-cover transition duration-700 group-hover:scale-[1.02]" :eager="true" />
                @else
                    <div class="bg-retina-lines aspect-[16/10]"></div>
                @endif
            </div>
            <div>
                <p class="eyebrow">Featured · {{ $featured->category?->name }}</p>
                <h2 class="mt-3 font-heading text-3xl font-medium leading-tight text-ink sm:text-4xl">
                    <a href="{{ $featured->urlPath() }}" class="after:absolute after:inset-0">{{ $featured->title }}</a>
                </h2>
                @if ($featured->excerpt)<p class="mt-4 text-lg leading-relaxed text-muted">{{ $featured->excerpt }}</p>@endif
                <p class="mt-6 text-sm text-muted">{{ $featured->author?->displayName() }} · {{ $featured->published_at?->format('j F Y') }} · {{ $featured->reading_time }} min read</p>
            </div>
        </article>
    @endif

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 scrollbar-none sm:mx-0 sm:flex-wrap sm:px-0">
            <a href="{{ route('articles.index') }}" class="filter-chip shrink-0" aria-pressed="{{ $categoryId ? 'false' : 'true' }}">All</a>
            @foreach ($categories as $c)
                <a href="{{ route('articles.category', $c) }}" class="filter-chip shrink-0" aria-pressed="{{ $categoryId === $c->id ? 'true' : 'false' }}">{{ $c->name }}</a>
            @endforeach
        </div>
        <div class="relative sm:w-72">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
            <input type="search" wire:model.live.debounce.350ms="search" class="input !py-2 !pl-9 text-sm" placeholder="Search articles" aria-label="Search articles">
        </div>
    </div>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        @if ($articles->isEmpty())
            <x-empty-state icon="heroicon-o-document-text" title="No articles found" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <x-article-card :article="$article" wire:key="article-{{ $article->id }}" />
                @endforeach
            </div>
        @endif
    </div>
    <div class="mt-12">{{ $articles->links() }}</div>
</div>
