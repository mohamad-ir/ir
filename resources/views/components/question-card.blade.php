@props(['question'])
<a href="{{ route('exam.practice', ['question' => $question->id]) }}" {{ $attributes->merge(['class' => 'group card-interactive flex flex-col p-5']) }}>
    <div class="flex items-center justify-between gap-3">
        <span class="chip chip-highlight">{{ $question->type?->getLabel() }}</span>
        <x-difficulty-badge :level="$question->difficulty" />
    </div>
    <h3 class="mt-3 font-heading text-lg font-semibold leading-snug text-ink group-hover:text-accent-ink transition">{{ $question->title }}</h3>
    <p class="mt-1.5 line-clamp-2 text-sm text-muted">{{ \App\Support\Html::excerpt($question->stem, 140) }}</p>
    <span class="mt-auto pt-4 link-arrow">Try this question <x-heroicon-m-arrow-right /></span>
</a>
