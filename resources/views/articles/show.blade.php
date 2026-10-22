@extends('layouts.app')
@section('content')
<article>
    <header class="relative overflow-hidden border-b border-line bg-surface">
        <div class="bg-retina-lines absolute inset-0 opacity-50" aria-hidden="true"></div>
        <div class="container-site relative pt-8 pb-12 sm:pt-10">
            @if ($isPreview)
                <div class="mb-6 rounded-ui bg-highlight/15 px-4 py-2.5 text-sm font-medium ring-1 ring-highlight/30">Preview — status: {{ $article->displayStatus() }}</div>
            @endif
            <x-breadcrumbs :items="array_filter(['Articles' => route('articles.index'), ($article->category?->name ?? '') => $article->category ? route('articles.category', $article->category) : null, $article->title => null])" />
            <div class="mx-auto mt-10 max-w-3xl text-center">
                @if ($article->category)<a href="{{ route('articles.category', $article->category) }}" class="eyebrow">{{ $article->category->name }}</a>@endif
                <h1 class="mt-4 font-heading text-4xl font-medium leading-[1.1] text-ink sm:text-5xl">{{ $article->title }}</h1>
                @if ($article->excerpt)<p class="lead mx-auto mt-5 max-w-2xl">{{ $article->excerpt }}</p>@endif
                <div class="mt-7 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-muted">
                    @if ($article->author)
                        <span class="inline-flex items-center gap-2">
                            <span class="grid size-8 place-items-center rounded-full bg-ink text-xs font-bold text-ink-contrast">{{ $article->author->initials() }}</span>
                            <span class="font-medium text-fg">{{ $article->author->displayName() }}</span>
                        </span>
                    @endif
                    <span>{{ $article->published_at?->format('j F Y') }}</span>
                    <span>{{ $article->reading_time }} min read</span>
                    @if ($article->updated_at && $article->published_at && $article->updated_at->gt($article->published_at->addDays(2)))
                        <span>Updated {{ $article->updated_at->format('j M Y') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </header>

    @if ($article->featuredMedia)
        <div class="container-site -mt-0 pt-10">
            <figure class="mx-auto max-w-5xl overflow-hidden rounded-card shadow-[var(--shadow-lift)]">
                <x-media-img :media="$article->featuredMedia" variant="lg" sizes="(min-width: 1024px) 1024px, 100vw" class="aspect-[2/1] w-full object-cover" :eager="true" />
            </figure>
        </div>
    @endif

    <div class="container-site grid gap-12 py-12 lg:grid-cols-[14rem_minmax(0,1fr)_14rem] lg:py-16">
        <aside class="hidden lg:block">
            @if (count($content['toc']) > 1)
                <nav x-data="tocSpy()" class="sticky top-24" aria-label="Contents">
                    <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Contents</p>
                    <ol class="mt-3 space-y-0.5 border-l border-line text-sm" role="list">
                        @foreach ($content['toc'] as $item)
                            <li><a href="#{{ $item['id'] }}" class="-ml-px block border-l-2 py-1.5 transition {{ $item['level'] === 3 ? 'pl-7 text-[0.8rem]' : 'pl-4' }}" :class="active === '{{ $item['id'] }}' ? 'border-accent font-semibold text-ink' : 'border-transparent text-muted hover:text-ink'">{{ $item['text'] }}</a></li>
                        @endforeach
                    </ol>
                </nav>
            @endif
        </aside>

        <div class="min-w-0">
            @if ($canView)
                <div class="prose prose-lg prose-clinical mx-auto" style="max-width: var(--container-prose)" data-toc-root>{!! $content['html'] !!}</div>
            @else
                <div class="prose prose-lg prose-clinical mx-auto max-h-80 overflow-hidden [mask-image:linear-gradient(to_bottom,black_40%,transparent)]" style="max-width: var(--container-prose)">{!! \App\Support\Html::teaser($content['html'], 3) !!}</div>
                <div class="mx-auto mt-4 max-w-xl rounded-card bg-ink p-8 text-ink-contrast">
                    <x-locked-content :content="$article" thing="article" />
                </div>
            @endif

            <div class="mx-auto" style="max-width: var(--container-prose)">
                @if ($canView)<x-references :references="$article->references ?? []" />@endif

                @if ($article->topics->isNotEmpty() || $article->tags->isNotEmpty())
                    <div class="mt-10 flex flex-wrap gap-2">
                        @foreach ($article->topics as $topic)<a href="{{ $topic->urlPath() }}" class="chip chip-accent">{{ $topic->name }}</a>@endforeach
                        @foreach ($article->tags as $tag)<a href="{{ route('search', ['q' => $tag->name]) }}" class="chip chip-outline">#{{ $tag->name }}</a>@endforeach
                    </div>
                @endif

                <div class="mt-10 flex flex-col gap-6 border-t border-line pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <x-share-buttons :url="$article->url()" :title="$article->title" />
                    @include('partials.bookmark', ['type' => 'article', 'model' => $article])
                </div>

                @if ($article->author)
                    <div class="mt-10 flex gap-5 rounded-card bg-subtle p-6">
                        <span class="grid size-14 shrink-0 place-items-center rounded-full bg-ink font-heading text-lg font-bold text-ink-contrast">{{ $article->author->initials() }}</span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Written by</p>
                            <p class="mt-1 font-heading text-lg font-semibold text-ink">{{ $article->author->displayName() }}</p>
                            @if ($article->author->bio)<p class="mt-1 text-sm text-muted">{{ $article->author->bio }}</p>@endif
                        </div>
                    </div>
                @endif

                @if ($canView && $article->commentsVisible() && ($article->commentsOpen() || $article->comments_count))
                    <div class="mt-14"><livewire:comments :item="$article" /></div>
                @endif
            </div>
        </div>
        <aside class="hidden lg:block"></aside>
    </div>
</article>

@if ($related->isNotEmpty())
    <section class="border-t border-line bg-subtle">
        <div class="container-site section">
            <x-section-heading eyebrow="Read next" heading="Related articles" />
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)<x-article-card :article="$item" />@endforeach
            </div>
        </div>
    </section>
@endif
@endsection
