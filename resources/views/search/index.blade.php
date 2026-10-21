@extends('layouts.app')
@php($labels = \App\Services\Search\SearchService::TYPES)
@section('content')
    <section class="border-b border-line bg-surface">
        <div class="container-site py-10 sm:py-14">
            <h1 class="sr-only">Search</h1>
            <form action="{{ route('search') }}" method="get" class="mx-auto flex max-w-3xl items-center gap-3 rounded-full bg-paper p-2 pl-6 shadow-[var(--shadow-lift)] ring-1 ring-line" role="search">
                <x-heroicon-o-magnifying-glass class="size-6 shrink-0 text-muted" />
                <label for="q" class="sr-only">Search</label>
                <input id="q" name="q" type="search" value="{{ $q }}" autofocus class="w-full border-0 bg-transparent py-2.5 text-lg text-fg placeholder:text-muted/70 focus:ring-0 focus:outline-none" placeholder="Search diseases, OCT signs, abbreviations…">
                @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
                <button class="btn btn-primary btn-lg !rounded-full">Search</button>
            </form>
            @if ($expansions)
                <p class="mt-4 text-center text-sm text-muted">Also searching for: @foreach ($expansions as $e)<span class="font-medium text-fg">{{ $e }}</span>@if(! $loop->last), @endif @endforeach</p>
            @endif
        </div>
    </section>

    <div class="container-site py-10 lg:py-14">
        @if ($q === '')
            <x-empty-state title="Search the whole library" text="Try a disease name or abbreviation such as CSR, VKH, DME or macular hole — cases, articles, videos and exam questions are searched together." />
        @else
            <div class="mb-8 flex flex-wrap items-center gap-2">
                <a href="{{ route('search', ['q' => $q]) }}" class="filter-chip" aria-pressed="{{ $type ? 'false' : 'true' }}">All <span class="opacity-60">{{ array_sum($counts) }}</span></a>
                @foreach ($counts as $t => $c)
                    <a href="{{ route('search', ['q' => $q, 'type' => $t]) }}" class="filter-chip" aria-pressed="{{ $type === $t ? 'true' : 'false' }}">{{ $labels[$t] }} <span class="opacity-60">{{ $c }}</span></a>
                @endforeach
            </div>

            @if ($results->isEmpty())
                <x-empty-state title="No results for “{{ $q }}”" text="Check the spelling, or try a broader term or the full disease name." />
            @else
                <ol class="divide-y divide-line" role="list">
                    @foreach ($results as $doc)
                        <li>
                            <a href="{{ url($doc->url) }}" class="group flex gap-5 py-5">
                                <span class="viewer-frame grid aspect-[4/3] w-24 shrink-0 place-items-center sm:w-32">
                                    @if ($doc->image_url)<img src="{{ $doc->image_url }}" alt="" class="size-full object-cover" loading="lazy">
                                    @else<span class="text-xs font-bold uppercase tracking-widest text-white/50">{{ $labels[$doc->type] ?? '' }}</span>@endif
                                </span>
                                <span class="min-w-0">
                                    <span class="eyebrow">{{ \Illuminate\Support\Str::singular($labels[$doc->type] ?? 'Page') }}</span>
                                    <span class="mt-1 block font-heading text-xl font-semibold leading-snug text-ink transition group-hover:text-accent-ink">{{ $doc->title }}</span>
                                    @if ($doc->summary)<span class="mt-1 block line-clamp-2 text-[0.9375rem] text-muted">{{ $doc->summary }}</span>@endif
                                    @if ($doc->published_at)<span class="mt-1.5 block text-xs text-muted">{{ $doc->published_at->format('j M Y') }}</span>@endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-10">{{ $results->links() }}</div>
            @endif
        @endif
    </div>
@endsection
