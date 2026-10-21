@extends('layouts.app')
@section('content')
    <section class="relative overflow-hidden bg-ink text-ink-contrast">
        <div class="absolute inset-0 opacity-40" style="background: radial-gradient(60% 80% at 85% 10%, color-mix(in oklab, var(--color-highlight) 35%, transparent), transparent 60%), radial-gradient(50% 60% at 0% 100%, color-mix(in oklab, var(--color-accent) 35%, transparent), transparent 60%)" aria-hidden="true"></div>
        <div class="container-site relative py-14 sm:py-20">
            <x-breadcrumbs :items="['Exam preparation' => null]" :light="true" />
            <p class="eyebrow !text-white/60 mt-8">FRCS · FRCOphth</p>
            <h1 class="mt-3 max-w-3xl font-heading text-4xl font-medium leading-[1.08] text-white sm:text-5xl lg:text-6xl">Prepare for your ophthalmology examinations</h1>
            <p class="mt-6 max-w-2xl text-lg text-white/75">High-yield revision notes, single best answer MCQs, image-based questions, viva practice and exam tips — written by examiners and recent candidates.</p>
            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('exam.practice') }}" class="btn btn-lg btn-light">Start practising <x-heroicon-m-arrow-right /></a>
                <a href="#topics" class="btn btn-lg btn-outline-light">Browse topics</a>
            </div>
            <dl class="mt-12 grid max-w-3xl grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach (\App\Enums\QuestionType::cases() as $type)
                    <div><dt class="text-sm text-white/55">{{ $type->getLabel() }}</dt><dd class="mt-1 font-heading text-3xl font-semibold text-white tabular-nums">{{ number_format($counts[$type->value] ?? 0) }}</dd></div>
                @endforeach
            </dl>
        </div>
    </section>

    <section id="topics" class="container-site section scroll-mt-20">
        <x-section-heading eyebrow="Syllabus" heading="Revision topics" intro="Each topic combines concise notes, exam tips and linked practice questions." />
        @if ($topics->isEmpty())
            <x-empty-state icon="heroicon-o-clipboard-document-check" title="Topics are being prepared" />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($topics as $topic)
                    <a href="{{ $topic->urlPath() }}" class="group card-interactive flex flex-col p-6">
                        <span class="grid size-12 place-items-center rounded-ui bg-highlight/15 text-[color-mix(in_oklab,var(--color-highlight)_55%,black)]">
                            <x-dynamic-component :component="'heroicon-o-'.($topic->icon ?: 'clipboard-document-check')" class="size-6" />
                        </span>
                        <p class="mt-5 text-xs font-semibold uppercase tracking-[.12em] text-muted">{{ $topic->exam?->getLabel() }}</p>
                        <h2 class="h-card mt-1">{{ $topic->title }}</h2>
                        @if ($topic->summary)<p class="mt-2 line-clamp-3 text-[0.9375rem] text-muted">{{ $topic->summary }}</p>@endif
                        <span class="mt-auto flex items-center justify-between pt-5 text-sm">
                            <span class="text-muted">{{ $topic->questions_count }} {{ \Illuminate\Support\Str::plural('question', $topic->questions_count) }}</span>
                            <span class="link-arrow">Revise <x-heroicon-m-arrow-right /></span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="border-t border-line bg-subtle">
        <div class="container-site section grid gap-10 lg:grid-cols-3">
            @foreach ([
                ['heroicon-o-check-circle', 'Single best answer', 'Exam-style MCQs with instant feedback and an explanation for every option.'],
                ['heroicon-o-photo', 'Image-based questions', 'OCT, fundus and angiography images you will meet in the written and clinical papers.'],
                ['heroicon-o-microphone', 'Viva practice', 'Structured model answers to rehearse out loud before your oral examination.'],
            ] as [$icon, $title, $text])
                <div>
                    <x-dynamic-component :component="$icon" class="size-8 text-accent" />
                    <h3 class="h-card mt-4">{{ $title }}</h3>
                    <p class="mt-2 text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
