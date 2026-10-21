@extends('layouts.app')
@section('content')
    <x-page-header :eyebrow="$topic->parent?->name ?? 'Topic'" :title="$topic->label()" :intro="$topic->description"
                   :breadcrumbs="array_filter(['Topics' => route('topics.index'), ($topic->parent?->name ?? '') => $topic->parent?->url(), $topic->name => null])">
        @if ($topic->children->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach ($topic->children as $child)<a href="{{ $child->urlPath() }}" class="filter-chip">{{ $child->abbreviation ?: $child->name }}</a>@endforeach
            </div>
        @endif
    </x-page-header>

    @if (filled((string) $body))
        <div class="container-site pt-12"><div class="prose prose-lg prose-clinical" style="max-width: var(--container-prose)">{!! $body !!}</div></div>
    @endif

    @if ($cases->isNotEmpty())
        <section class="container-site section">
            <x-section-heading eyebrow="OCT cases" :heading="number_format($caseCount).' '.\Illuminate\Support\Str::plural('case', $caseCount).' on '.$topic->name" action-label="Filter in case library" :action-url="route('cases.index', ['topic' => $topic->slug])" />
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($cases as $case)<x-case-card :case="$case" />@endforeach</div>
        </section>
    @endif

    @if ($videos->isNotEmpty())
        <section class="border-t border-line bg-subtle"><div class="container-site section">
            <x-section-heading eyebrow="Videos" heading="Watch" />
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">@foreach ($videos as $video)<x-video-card :video="$video" />@endforeach</div>
        </div></section>
    @endif

    @if ($articles->isNotEmpty())
        <section class="container-site section">
            <x-section-heading eyebrow="Articles" heading="Read" />
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($articles as $article)<x-article-card :article="$article" />@endforeach</div>
        </section>
    @endif

    @if ($questions->isNotEmpty())
        <section class="border-t border-line bg-subtle"><div class="container-site section">
            <x-section-heading eyebrow="Exam preparation" heading="Test yourself" />
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ($questions as $q)<x-question-card :question="$q" />@endforeach</div>
        </div></section>
    @endif

    @if ($cases->isEmpty() && $videos->isEmpty() && $articles->isEmpty() && $questions->isEmpty())
        <div class="container-site section"><x-empty-state icon="heroicon-o-sparkles" title="Material on this topic is coming soon" text="Subscribe to the newsletter to hear when new cases are published." /></div>
    @endif
@endsection
