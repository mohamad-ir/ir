@extends('layouts.app')
@php($chapters = collect($video->chapters ?? [])->filter(fn ($c) => filled($c['title'] ?? null)))
@section('content')
<div class="bg-ink text-ink-contrast">
    <div class="container-site pt-6 pb-10 sm:pt-8">
        @if ($isPreview)
            <div class="mb-6 rounded-ui bg-highlight/20 px-4 py-2.5 text-sm font-medium">Preview — status: {{ $video->displayStatus() }}</div>
        @endif
        <x-breadcrumbs :items="['Videos' => route('videos.index'), $video->title => null]" :light="true" />
        <div class="mx-auto mt-6 max-w-5xl">
            @if ($canView)
                @include('videos.partials.player', ['video' => $video, 'startAt' => $startAt])
            @else
                <div class="viewer-frame relative aspect-video">
                    @if ($video->thumbnail())<img src="{{ $video->thumbnail() }}" alt="" class="size-full object-cover opacity-40">@endif
                    <div class="absolute inset-0 grid place-items-center p-6 text-center">
                        <div>
                            <x-heroicon-o-lock-closed class="mx-auto size-8 text-white/70" />
                            <p class="mt-3 font-heading text-2xl font-semibold">Free for registered members</p>
                            <div class="mt-5 flex justify-center gap-3"><a href="{{ route('register') }}" class="btn btn-light">Create free account</a><a href="{{ route('login') }}" class="btn btn-outline-light">Sign in</a></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="container-site grid gap-12 py-10 lg:grid-cols-[minmax(0,1fr)_20rem] lg:py-14">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            @if ($video->category)<span class="eyebrow">{{ $video->category->name }}</span>@endif
            @if ($video->level)<x-difficulty-badge :level="$video->level" class="ml-2" />@endif
        </div>
        <h1 class="mt-3 font-heading text-3xl font-medium leading-tight text-ink sm:text-4xl">{{ $video->title }}</h1>
        <p class="mt-3 text-sm text-muted">{{ $video->published_at?->format('j F Y') }} @if($video->durationLabel()) · {{ $video->durationLabel() }} @endif @if($video->author) · {{ $video->author->displayName() }} @endif</p>

        @if ($video->summary)<p class="lead mt-6">{{ $video->summary }}</p>@endif
        @if (filled($video->description))
            <div class="prose prose-lg prose-clinical mt-6">{!! \App\Support\RichText::render($video->description) !!}</div>
        @endif

        @if (filled($video->transcript) && $canView)
            <details class="group mt-10 rounded-card ring-1 ring-line">
                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 font-semibold text-ink [&::-webkit-details-marker]:hidden">
                    <span class="inline-flex items-center gap-2"><x-heroicon-o-document-text class="size-5 text-muted" /> Transcript</span>
                    <x-heroicon-m-chevron-down class="size-5 transition group-open:rotate-180" />
                </summary>
                <div class="max-h-[28rem] overflow-y-auto border-t border-line px-5 py-4 text-[0.95rem] leading-relaxed whitespace-pre-line text-fg/85">{{ $video->transcript }}</div>
            </details>
        @endif

        <div class="mt-10 flex flex-col gap-6 border-t border-line pt-8 sm:flex-row sm:items-center sm:justify-between">
            <x-share-buttons :url="$video->url()" :title="$video->title" />
            @include('partials.bookmark', ['type' => 'video', 'model' => $video])
        </div>
    </div>

    <aside class="space-y-8">
        @if ($chapters->isNotEmpty() && $canView)
            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Chapters</p>
                <ol class="mt-3 space-y-0.5" role="list">
                    @foreach ($chapters as $chapter)
                        <li><button type="button" x-data x-on:click="$dispatch('seek-video', {{ \App\Services\Seo\SchemaFactory::seconds($chapter['time'] ?? 0) }}); window.scrollTo({ top: 0, behavior: 'smooth' })" class="flex w-full items-start gap-3 rounded-ui px-2 py-2 text-left text-sm transition hover:bg-ink/5">
                            <span class="w-12 shrink-0 font-semibold tabular-nums text-accent-ink">{{ $chapter['time'] ?? '0:00' }}</span><span class="text-fg">{{ $chapter['title'] }}</span>
                        </button></li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if ($video->topics->isNotEmpty())
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Topics</p>
                <div class="mt-3 flex flex-wrap gap-2">@foreach ($video->topics as $topic)<a href="{{ $topic->urlPath() }}" class="chip chip-accent">{{ $topic->name }}</a>@endforeach</div>
            </div>
        @endif

        @if ($related->isNotEmpty())
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Up next</p>
                <div class="mt-4 space-y-5">
                    @foreach ($related as $item)
                        <a href="{{ $item->urlPath() }}" class="group flex gap-3">
                            <span class="viewer-frame relative aspect-video w-32 shrink-0">@if($item->thumbnail())<img src="{{ $item->thumbnail() }}" alt="" class="size-full object-cover" loading="lazy">@endif
                                @if($item->durationLabel())<span class="absolute bottom-1 right-1 rounded bg-black/75 px-1 text-[0.65rem] font-semibold text-white">{{ $item->durationLabel() }}</span>@endif</span>
                            <span class="text-sm font-semibold leading-snug text-ink transition group-hover:text-accent-ink">{{ $item->title }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </aside>
</div>
@endsection
