@extends('layouts.app')
@section('content')
    <section class="relative overflow-hidden border-b border-line bg-surface">
        <div class="bg-retina-lines absolute inset-0 opacity-60" aria-hidden="true"></div>
        <div class="container-site relative py-10 sm:py-14">
            @if ($isPreview)
                <div class="mb-6 rounded-ui bg-highlight/20 px-4 py-2.5 text-sm font-medium">Preview — status: {{ $gallery->displayStatus() }}</div>
            @endif
            <x-breadcrumbs :items="['Galleries' => route('galleries.index'), $gallery->title => null]" class="mb-6" />
            <div class="flex flex-wrap items-center gap-2">
                <span class="eyebrow">Image gallery</span>
                @foreach ($gallery->topics as $topic)<a href="{{ $topic->urlPath() }}" class="chip chip-accent">{{ $topic->name }}</a>@endforeach
            </div>
            <h1 class="h-display mt-3 max-w-4xl">{{ $gallery->title }}</h1>
            @if ($gallery->summary)<p class="lead mt-4 max-w-3xl">{{ $gallery->summary }}</p>@endif
            <p class="mt-4 text-sm text-muted">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('image', $items->count()) }}@if ($gallery->published_at) · {{ $gallery->published_at->format('j F Y') }}@endif</p>
        </div>
    </section>

    <div @class(['section', 'bg-ink' => $gallery->dark_viewer])>
        <div class="container-site">
            @if (filled($gallery->description))
                <div @class(['prose prose-lg prose-clinical mb-10 max-w-3xl', 'prose-invert' => $gallery->dark_viewer])>{!! \App\Support\RichText::render($gallery->description) !!}</div>
            @endif

            @if (! $canView)
                <div class="relative overflow-hidden rounded-card bg-ink p-8 text-ink-contrast sm:p-14">
                    @if ($cover = $gallery->cover())
                        <x-media-img :media="$cover" variant="md" class="absolute inset-0 size-full object-cover opacity-20 blur-sm" alt="" />
                    @endif
                    <x-locked-content :content="$gallery" thing="gallery" class="relative" />
                </div>
            @elseif ($items->isEmpty())
                <x-empty-state icon="heroicon-o-photo" title="This gallery is empty" text="Images will be added soon." />
            @else
                @include('galleries.grid', ['items' => $items, 'layout' => $gallery->layout, 'deepLink' => true])
                <p @class(['mt-4 text-center text-xs', 'text-white/50' => $gallery->dark_viewer, 'text-muted' => ! $gallery->dark_viewer])>Tap an image to view it full screen · use ← → to browse</p>
            @endif

            <div @class(['mt-10 border-t pt-8', 'border-white/10' => $gallery->dark_viewer, 'border-line' => ! $gallery->dark_viewer])>
                <x-share-buttons :url="$gallery->url()" :title="$gallery->title" />
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="section tone-muted">
            <div class="container-site">
                <x-section-heading eyebrow="More galleries" heading="Keep browsing" action-label="All galleries" :action-url="route('galleries.index')" />
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)<x-gallery-card :gallery="$item" />@endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
