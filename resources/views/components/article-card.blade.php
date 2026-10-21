@props(['article', 'layout' => 'card'])
@if ($layout === 'list')
    <a href="{{ $article->urlPath() }}" {{ $attributes->merge(['class' => 'group flex gap-5 rounded-card p-3 -mx-3 transition hover:bg-ink/[.035]']) }}>
        @if ($article->featuredMedia)
            <div class="aspect-[4/3] w-32 shrink-0 overflow-hidden rounded-ui bg-subtle sm:w-40">
                <x-media-img :media="$article->featuredMedia" variant="thumb" sizes="160px" class="size-full object-cover transition duration-500 group-hover:scale-[1.04]" />
            </div>
        @endif
        <div class="min-w-0 py-1">
            <p class="eyebrow">{{ $article->category?->name ?? 'Article' }}</p>
            <h3 class="mt-1.5 font-heading text-lg font-semibold leading-snug text-ink transition group-hover:text-accent-ink">{{ $article->title }}</h3>
            <p class="mt-1 text-sm text-muted">{{ $article->published_at?->format('j M Y') }} · {{ $article->reading_time }} min read</p>
        </div>
    </a>
@else
    <article {{ $attributes->merge(['class' => 'group card-interactive relative flex flex-col overflow-hidden']) }}>
        <div class="aspect-[16/9] overflow-hidden bg-subtle">
            @if ($article->featuredMedia)
                <x-media-img :media="$article->featuredMedia" variant="md" sizes="(min-width: 1024px) 400px, 100vw" class="size-full object-cover transition duration-700 group-hover:scale-[1.035]" />
            @else
                <div class="bg-retina-lines grid size-full place-items-center bg-ink/[.04]"><x-heroicon-o-document-text class="size-10 text-ink/25" /></div>
            @endif
        </div>
        <div class="flex flex-1 flex-col p-5">
            <p class="eyebrow">{{ $article->category?->name ?? 'Article' }}</p>
            <h3 class="mt-2 font-heading text-[1.3rem] font-semibold leading-snug text-ink">
                <a href="{{ $article->urlPath() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $article->title }}</a>
            </h3>
            @if ($article->excerpt)
                <p class="mt-2 line-clamp-3 text-[0.9375rem] leading-relaxed text-muted">{{ $article->excerpt }}</p>
            @endif
            <div class="mt-auto flex items-center gap-2 pt-5 text-sm text-muted">
                <span>{{ $article->published_at?->format('j M Y') }}</span>
                <span aria-hidden="true">·</span>
                <span>{{ $article->reading_time }} min read</span>
            </div>
        </div>
    </article>
@endif
