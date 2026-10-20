<x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" :action-label="'Open the exam hub'" :action-url="route('exam.index')" />
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($data['topics'] ?? [] as $topic)
        <a href="{{ $topic->urlPath() }}" class="group card-interactive flex items-start gap-4 p-5">
            <span class="grid size-11 shrink-0 place-items-center rounded-ui bg-highlight/15 text-[color-mix(in_oklab,var(--color-highlight)_55%,black)]">
                <x-dynamic-component :component="'heroicon-o-'.($topic->icon ?: 'clipboard-document-check')" class="size-5.5" />
            </span>
            <span>
                <span class="block font-semibold text-ink group-hover:text-accent-ink transition">{{ $topic->title }}</span>
                <span class="mt-0.5 block text-sm text-muted">{{ $topic->questions_count }} {{ \Illuminate\Support\Str::plural('question', $topic->questions_count) }} · {{ $topic->exam?->getLabel() }}</span>
            </span>
        </a>
    @endforeach
</div>
<div class="mt-8 flex flex-wrap items-center gap-4">
    <a href="{{ route('exam.practice') }}" class="btn btn-primary">Start practice session <x-heroicon-m-arrow-right /></a>
    <span class="text-sm text-muted">{{ number_format($data['question_count'] ?? 0) }} questions with explanations</span>
</div>
