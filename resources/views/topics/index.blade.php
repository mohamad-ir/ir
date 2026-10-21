@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="Browse" title="Topics A–Z" intro="Every case, article, video and exam question is linked to its disease or clinical topic." :breadcrumbs="['Topics' => null]" />
    <div class="container-site py-12 lg:py-16">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($topics as $topic)
                <section>
                    <a href="{{ $topic->urlPath() }}" class="group flex items-baseline justify-between gap-3 border-b border-line pb-3">
                        <h2 class="font-heading text-2xl font-semibold text-ink group-hover:text-accent-ink transition">{{ $topic->name }}</h2>
                        <span class="text-sm text-muted tabular-nums">{{ $topic->cases_count + $topic->children->sum('cases_count') }} cases</span>
                    </a>
                    @if ($topic->children->isNotEmpty())
                        <ul class="mt-3 space-y-1" role="list">
                            @foreach ($topic->children as $child)
                                <li><a href="{{ $child->urlPath() }}" class="flex items-center justify-between rounded-ui px-2 py-1.5 text-[0.9375rem] transition hover:bg-ink/5">
                                    <span>{{ $child->name }} @if($child->abbreviation)<span class="text-muted">({{ $child->abbreviation }})</span>@endif</span>
                                    <span class="text-xs text-muted tabular-nums">{{ $child->cases_count }}</span>
                                </a></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
@endsection
