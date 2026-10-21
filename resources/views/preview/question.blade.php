@extends('layouts.app')
@section('content')
    <div class="bg-highlight/15 py-2 text-center text-sm font-medium text-ink">Preview — {{ $question->displayStatus() }} · answers shown for review</div>
    <div class="container-site py-12">
        <article class="card mx-auto max-w-3xl overflow-hidden">
            <header class="flex flex-wrap items-center gap-2 border-b border-line bg-subtle px-8 py-4">
                <span class="chip chip-highlight">{{ $question->type->getLabel() }}</span>
                @if ($question->examTopic)<span class="chip">{{ $question->examTopic->title }}</span>@endif
            </header>
            <div class="px-8 py-7">
                <h1 class="font-heading text-2xl font-semibold text-ink">{{ $question->title }}</h1>
                <div class="prose prose-lg prose-clinical mt-5">{!! \App\Support\RichText::render($question->stem) !!}</div>
                @if ($question->media)
                    <figure class="viewer-frame mt-6"><x-media-img :media="$question->media" variant="lg" class="mx-auto max-h-[30rem] w-auto object-contain" /></figure>
                @endif
                @if ($question->isMultipleChoice())
                    <ol class="mt-6 space-y-2">
                        @foreach ($question->lettered() as $key => $option)
                            <li class="flex gap-3 rounded-ui px-4 py-3 ring-1 {{ ! empty($option['is_correct']) ? 'bg-emerald-50 ring-emerald-600' : 'ring-line-strong' }}"><strong>{{ $key }}</strong> {{ $option['text'] }}</li>
                        @endforeach
                    </ol>
                @endif
            </div>
            <div class="border-t border-line bg-subtle/60 px-8 py-7">
                @if (filled($question->model_answer))<h2 class="font-heading text-lg font-semibold">Model answer</h2><div class="prose prose-clinical mt-2">{!! \App\Support\RichText::render($question->model_answer) !!}</div>@endif
                @if (filled($question->explanation))<h2 class="mt-6 font-heading text-lg font-semibold">Explanation</h2><div class="prose prose-clinical mt-2">{!! \App\Support\RichText::render($question->explanation, $question->references ?? []) !!}</div>@endif
                <x-references :references="$question->references ?? []" />
            </div>
        </article>
    </div>
@endsection
