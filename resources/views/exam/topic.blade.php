@extends('layouts.app')
@section('content')
    <x-page-header :eyebrow="$topic->exam?->getLabel()" :title="$topic->title" :intro="$topic->summary"
                   :breadcrumbs="['Exam preparation' => route('exam.index'), $topic->title => null]">
        <div class="mt-8 flex flex-wrap gap-3">
            @if ($questions->isNotEmpty())
                <a href="{{ route('exam.practice', ['topic' => $topic->slug]) }}" class="btn btn-primary">Practise {{ $questions->count() }} {{ \Illuminate\Support\Str::plural('question', $questions->count()) }} <x-heroicon-m-arrow-right /></a>
            @endif
        </div>
    </x-page-header>
    <div class="container-site grid gap-12 py-12 lg:grid-cols-[minmax(0,1fr)_18rem] lg:py-16">
        <div class="min-w-0">
            @if (filled($content['html']))
                <div class="prose prose-lg prose-clinical" style="max-width: var(--container-prose)" data-toc-root>{!! $content['html'] !!}</div>
            @endif
            @if ($questions->isNotEmpty())
                <h2 class="mt-14 font-heading text-2xl font-semibold text-ink">Practice questions</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($questions as $q)<x-question-card :question="$q" />@endforeach
                </div>
            @endif
        </div>
        <aside class="hidden lg:block">
            @if (count($content['toc']) > 1)
                <nav x-data="tocSpy()" class="sticky top-24" aria-label="Contents">
                    <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">On this page</p>
                    <ol class="mt-3 space-y-0.5 border-l border-line text-sm" role="list">
                        @foreach ($content['toc'] as $item)
                            <li><a href="#{{ $item['id'] }}" class="-ml-px block border-l-2 py-1.5 pl-4 transition" :class="active === '{{ $item['id'] }}' ? 'border-accent font-semibold text-ink' : 'border-transparent text-muted hover:text-ink'">{{ $item['text'] }}</a></li>
                        @endforeach
                    </ol>
                </nav>
            @endif
        </aside>
    </div>
@endsection
