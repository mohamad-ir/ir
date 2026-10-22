@extends('layouts.app')
@section('content')
<x-banner-slider placement="course" />
<section class="relative overflow-hidden bg-ink text-ink-contrast">
    @if ($course->featuredMedia)
        <x-media-img :media="$course->featuredMedia" variant="lg" sizes="100vw" class="absolute inset-0 size-full object-cover opacity-20" :eager="true" alt="" />
        <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/40"></div>
    @else
        <div class="bg-retina-lines absolute inset-0 opacity-20"></div>
    @endif
    <div class="container-site relative py-12 sm:py-16 lg:py-20">
        @if ($isPreview)
            <div class="mb-6 rounded-ui bg-highlight/20 px-4 py-2.5 text-sm font-medium">Preview — status: {{ $course->displayStatus() }}</div>
        @endif
        <x-breadcrumbs :items="['Courses' => route('courses.index'), $course->title => null]" :light="true" />
        <div class="mt-6 max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                <span class="eyebrow !text-white/70">Online course</span>
                @foreach ($course->topics->take(3) as $topic)<span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium">{{ $topic->name }}</span>@endforeach
            </div>
            <h1 class="mt-4 font-heading text-4xl font-medium leading-[1.1] text-white sm:text-5xl">{{ $course->title }}</h1>
            @if ($course->subtitle)<p class="mt-5 text-xl leading-relaxed text-white/80">{{ $course->subtitle }}</p>@endif
            <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-white/70">
                <span class="inline-flex items-center gap-1.5"><x-heroicon-m-play-circle class="size-4" /> {{ $lessonCount }} lessons</span>
                @if ($course->durationLabel())<span class="inline-flex items-center gap-1.5"><x-heroicon-m-clock class="size-4" /> {{ $course->durationLabel() }}</span>@endif
                @if ($course->level)<span class="inline-flex items-center gap-1.5"><x-heroicon-m-signal class="size-4" /> {{ $course->level->getLabel() }}</span>@endif
                @if ($course->author)<span class="inline-flex items-center gap-1.5"><x-heroicon-m-user class="size-4" /> {{ $course->author->displayName() }}</span>@endif
            </div>
        </div>
    </div>
</section>

<div class="container-site grid gap-12 py-12 lg:grid-cols-[minmax(0,1fr)_23rem] lg:py-16">
    <div class="min-w-0 space-y-14">
        @if ($course->trailerEmbedUrl())
            <div class="viewer-frame aspect-video">
                <iframe src="{{ $course->trailerEmbedUrl() }}" title="Course introduction" class="size-full" loading="lazy" allow="fullscreen; picture-in-picture" allowfullscreen></iframe>
            </div>
        @endif

        @if (collect($course->outcomes)->filter(fn ($o) => filled($o['text'] ?? null))->isNotEmpty())
            <section>
                <h2 class="h-section !text-3xl">What you will learn</h2>
                <ul class="mt-6 grid gap-x-8 gap-y-3 sm:grid-cols-2" role="list">
                    @foreach ($course->outcomes as $outcome)
                        @continue(blank($outcome['text'] ?? null))
                        <li class="flex gap-3 text-[0.98rem] leading-relaxed"><x-heroicon-s-check class="mt-1 size-5 shrink-0 text-accent" /> {{ $outcome['text'] }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (filled($course->description))
            <section>
                <h2 class="h-section !text-3xl">About this course</h2>
                <div class="prose prose-lg prose-clinical mt-6">{!! \App\Support\RichText::render($course->description) !!}</div>
            </section>
        @endif

        <section>
            <div class="flex items-end justify-between gap-4">
                <h2 class="h-section !text-3xl">Curriculum</h2>
                <span class="text-sm text-muted">{{ $lessonCount }} lessons{{ $course->durationLabel() ? " · ".$course->durationLabel() : "" }}</span>
            </div>
            <div class="mt-6">
                @include('courses.partials.curriculum', ['curriculum' => $curriculum, 'course' => $course, 'completed' => $completed, 'canAccess' => $canAccess])
            </div>
        </section>

        @if ($course->author)
            <section class="flex flex-col gap-5 rounded-card bg-surface p-6 ring-1 ring-line sm:flex-row sm:items-center">
                @if ($course->author->avatarUrl())
                    <img src="{{ $course->author->avatarUrl() }}" alt="" class="size-20 shrink-0 rounded-full object-cover">
                @else
                    <span class="grid size-20 shrink-0 place-items-center rounded-full bg-accent font-heading text-2xl font-bold text-accent-contrast">{{ $course->author->initials() }}</span>
                @endif
                <div>
                    <p class="eyebrow">Your teacher</p>
                    <p class="mt-1 font-heading text-xl font-semibold text-ink">{{ $course->author->displayName() }}</p>
                    @if ($course->author->bio)<p class="mt-2 text-[0.95rem] text-muted">{{ $course->author->bio }}</p>@endif
                </div>
            </section>
        @endif

        @php $faqs = collect($course->faqs)->filter(fn ($f) => filled($f['question'] ?? null)); @endphp
        @if ($faqs->isNotEmpty())
            <section>
                <h2 class="h-section !text-3xl">Questions</h2>
                <div class="mt-6 divide-y divide-line rounded-card bg-surface ring-1 ring-line">
                    @foreach ($faqs as $faq)
                        <details class="group px-5 py-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink [&::-webkit-details-marker]:hidden">{{ $faq['question'] }}<x-heroicon-m-chevron-down class="size-5 shrink-0 transition group-open:rotate-180" /></summary>
                            <p class="mt-3 text-[0.95rem] leading-relaxed text-muted">{{ $faq['answer'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <aside class="lg:order-none -order-1">
        @include('courses.partials.buy-box')
    </aside>
</div>
@endsection
