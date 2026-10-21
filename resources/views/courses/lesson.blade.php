@extends('layouts.app')
@section('content')
<div class="border-b border-line bg-surface">
    <div class="container-site flex flex-wrap items-center justify-between gap-4 py-4">
        <div class="min-w-0">
            <a href="{{ $course->urlPath() }}" class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink"><x-heroicon-m-arrow-left class="size-4" /> {{ $course->title }}</a>
        </div>
        @if ($canAccessCourse)
            <div class="flex items-center gap-3 text-sm text-muted">
                <span class="tabular-nums">{{ $progress }}% complete</span>
                <span class="h-1.5 w-32 overflow-hidden rounded-full bg-line"><span class="block h-full rounded-full bg-accent" style="width: {{ $progress }}%"></span></span>
            </div>
        @endif
    </div>
</div>

<div class="container-site grid gap-10 py-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:py-10">
    <div class="min-w-0">
        @if ($canView)
            @if ($lesson->hasVideo())
                <div class="viewer-frame aspect-video shadow-[var(--shadow-pop)]">
                    @if ($lesson->isFilePlayer())
                        <video src="{{ $lesson->embedUrl() }}" controls playsinline preload="metadata" class="size-full bg-black" controlslist="nodownload"></video>
                    @else
                        <iframe src="{{ $lesson->embedUrl() }}" title="{{ $lesson->title }}" class="size-full" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    @endif
                </div>
            @endif

            <div class="mt-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    @if ($lesson->section)<p class="eyebrow">{{ $lesson->section->title }}</p>@endif
                    <h1 class="mt-1 font-heading text-3xl font-medium leading-tight text-ink sm:text-4xl">{{ $lesson->title }}</h1>
                </div>
                @if ($canAccessCourse)
                    @php $isDone = in_array($lesson->id, $completed, true); @endphp
                    <form method="post" action="{{ route('courses.complete', [$course->slug, $lesson->slug]) }}">@csrf
                        @if ($isDone)
                            <input type="hidden" name="undo" value="1">
                            <button class="btn btn-secondary btn-sm"><x-heroicon-s-check-circle class="text-accent" /> Completed</button>
                        @else
                            <button class="btn btn-secondary btn-sm"><x-heroicon-o-check-circle /> Mark as complete</button>
                        @endif
                    </form>
                @endif
            </div>

            @if ($lesson->summary)<p class="lead mt-4">{{ $lesson->summary }}</p>@endif
            @if (filled($lesson->body))
                <div class="prose prose-lg prose-clinical mt-6">{!! \App\Support\RichText::render($lesson->body) !!}</div>
            @endif

            @php $files = $lesson->attachmentMedia(); @endphp
            @if ($files->isNotEmpty())
                <div class="mt-10">
                    <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Downloads</p>
                    <ul class="mt-3 divide-y divide-line rounded-card bg-surface ring-1 ring-line" role="list">
                        @foreach ($files as $file)
                            <li><a href="{{ $file->url() }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-ink/[.03]"><x-heroicon-o-arrow-down-tray class="size-5 text-muted" /> <span class="flex-1">{{ $file->title ?: $file->original_name }}</span><span class="text-xs uppercase text-muted">{{ $file->extension }}</span></a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-12 flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
                @if ($previous)
                    <a href="{{ route('courses.lesson', [$course->slug, $previous->slug]) }}" class="btn btn-ghost"><x-heroicon-m-arrow-left /> {{ \Illuminate\Support\Str::limit($previous->title, 40) }}</a>
                @else <span></span> @endif
                @if ($next)
                    @if ($canAccessCourse)
                        <form method="post" action="{{ route('courses.complete', [$course->slug, $lesson->slug]) }}">@csrf
                            <input type="hidden" name="continue" value="1">
                            <button class="btn btn-primary">Complete & continue <x-heroicon-m-arrow-right /></button>
                        </form>
                    @else
                        <a href="{{ route('courses.lesson', [$course->slug, $next->slug]) }}" class="btn btn-primary">Next lesson <x-heroicon-m-arrow-right /></a>
                    @endif
                @elseif ($canAccessCourse && ! in_array($lesson->id, $completed, true))
                    <form method="post" action="{{ route('courses.complete', [$course->slug, $lesson->slug]) }}">@csrf
                        <button class="btn btn-primary">Finish the course <x-heroicon-m-check /></button>
                    </form>
                @endif
            </div>
        @else
            <div class="overflow-hidden rounded-card bg-ink p-8 text-center text-ink-contrast sm:p-14">
                <x-heroicon-o-lock-closed class="mx-auto size-8 text-white/70" />
                <h1 class="mt-4 font-heading text-3xl font-medium text-white">{{ $lesson->title }}</h1>
                <p class="mx-auto mt-3 max-w-md text-white/70">This lesson is part of <strong>{{ $course->title }}</strong>. Enrol to unlock every lesson{{ $course->included_in_membership ? ' — or get it with membership' : '' }}.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ $course->urlPath() }}" class="btn btn-light">{{ $course->isFree() ? 'Enrol for free' : 'Get the course — '.$course->priceLabel() }}</a>
                    @if ($course->included_in_membership && ! $isMember)<a href="{{ route('membership') }}" class="btn btn-outline-light">Membership options</a>@endif
                </div>
            </div>
        @endif
    </div>

    <aside class="lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:self-start lg:overflow-y-auto">
        <p class="px-2 text-xs font-semibold uppercase tracking-[.14em] text-muted">Course content</p>
        <div class="mt-2">
            @include('courses.partials.curriculum', ['curriculum' => $curriculum, 'course' => $course, 'completed' => $completed, 'canAccess' => $canAccessCourse, 'current' => $lesson, 'compact' => true])
        </div>
    </aside>
</div>
@endsection
