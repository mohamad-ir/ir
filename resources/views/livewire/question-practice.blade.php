<div class="grid gap-10 lg:grid-cols-[16rem_1fr]">
    <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">This session</p>
            <p class="mt-2 font-heading text-4xl font-semibold text-ink tabular-nums">{{ $sessionCorrect }}<span class="text-xl text-muted">/{{ $sessionAnswered }}</span></p>
            <p class="text-sm text-muted">correct answers</p>
            @if ($sessionAnswered)
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-accent transition-all" style="width: {{ round($sessionCorrect / max(1, $sessionAnswered) * 100) }}%"></div></div>
            @endif
            @guest
                <p class="mt-4 text-xs text-muted"><a href="{{ route('register') }}" class="link">Create a free account</a> to track your progress over time.</p>
            @endguest
        </div>
        <div class="space-y-4">
            <div>
                <label for="q-exam" class="field-label">Examination</label>
                <select id="q-exam" wire:model.live="exam" class="input text-sm">
                    <option value="">All examinations</option>
                    @foreach ($exams as $e)<option value="{{ $e->value }}">{{ $e->getLabel() }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="q-topic" class="field-label">Topic</label>
                <select id="q-topic" wire:model.live="topic" class="input text-sm">
                    <option value="">All topics</option>
                    @foreach ($topics as $t)<option value="{{ $t->slug }}">{{ $t->title }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="q-type" class="field-label">Question type</label>
                <select id="q-type" wire:model.live="type" class="input text-sm">
                    <option value="">All types</option>
                    @foreach ($types as $t)<option value="{{ $t->value }}">{{ $t->getLabel() }}</option>@endforeach
                </select>
            </div>
            <p class="text-xs text-muted">{{ number_format($available) }} {{ \Illuminate\Support\Str::plural('question', $available) }} available</p>
        </div>
    </aside>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        @if (! $question)
            <x-empty-state icon="heroicon-o-clipboard-document-check" title="No questions match these filters" text="Choose another topic or question type." />
        @else
            <article class="card overflow-hidden" wire:key="question-{{ $question->id }}">
                <header class="flex flex-wrap items-center gap-2 border-b border-line bg-subtle px-6 py-4 sm:px-8">
                    <span class="chip chip-highlight">{{ $question->type->getLabel() }}</span>
                    @if ($question->examTopic)<span class="chip">{{ $question->examTopic->title }}</span>@endif
                    <x-difficulty-badge :level="$question->difficulty" class="ml-auto" />
                </header>

                <div class="px-6 py-7 sm:px-8">
                    <h2 class="font-heading text-2xl font-semibold leading-snug text-ink">{{ $question->title }}</h2>

                    @if (! $canView)
                        <div class="mt-6 rounded-card bg-accent/[.07] p-6 ring-1 ring-accent/20">
                            <x-locked-content :content="$question" :dark="false" thing="question" />
                        </div>
                    @else
                        <div class="prose prose-lg prose-clinical mt-5">{!! $stem !!}</div>

                        @if ($question->media)
                            <figure class="viewer-frame mt-6">
                                <x-media-img :media="$question->media" variant="lg" sizes="(min-width: 1024px) 760px, 100vw" class="mx-auto max-h-[32rem] w-auto object-contain" />
                                @if ($question->media->caption)<figcaption class="px-4 py-2 text-sm text-white/60">{{ $question->media->caption }}</figcaption>@endif
                            </figure>
                        @endif

                        @if ($question->isMultipleChoice())
                            <fieldset class="mt-7 space-y-2.5">
                                <legend class="sr-only">Choose one answer</legend>
                                @foreach ($question->lettered() as $key => $option)
                                    @php
                                        $isCorrect = ! empty($option['is_correct']);
                                        $isSelected = $selected === $key;
                                        $state = $answered ? ($isCorrect ? 'correct' : ($isSelected ? 'wrong' : 'idle')) : ($isSelected ? 'selected' : 'idle');
                                    @endphp
                                    <button type="button" wire:click="choose('{{ $key }}')" @disabled($answered)
                                        @class([
                                            'flex w-full items-start gap-4 rounded-ui px-4 py-3.5 text-left ring-1 ring-inset transition',
                                            'ring-line-strong hover:bg-ink/[.03] hover:ring-ink/30' => $state === 'idle' && ! $answered,
                                            'ring-line opacity-70' => $state === 'idle' && $answered,
                                            'bg-accent/[.06] ring-2 ring-accent' => $state === 'selected',
                                            'bg-emerald-50 ring-2 ring-emerald-600' => $state === 'correct',
                                            'bg-red-50 ring-2 ring-red-500' => $state === 'wrong',
                                        ]) aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                                        <span @class(['grid size-7 shrink-0 place-items-center rounded-full text-sm font-bold',
                                            'bg-ink/[.07] text-ink' => in_array($state, ['idle']),
                                            'bg-accent text-accent-contrast' => $state === 'selected',
                                            'bg-emerald-600 text-white' => $state === 'correct',
                                            'bg-red-500 text-white' => $state === 'wrong'])>{{ $key }}</span>
                                        <span class="pt-0.5 text-[0.98rem]">{{ $option['text'] }}</span>
                                        @if ($answered && $isCorrect)<x-heroicon-m-check-circle class="ml-auto size-6 shrink-0 text-emerald-600" />@endif
                                        @if ($answered && $state === 'wrong')<x-heroicon-m-x-circle class="ml-auto size-6 shrink-0 text-red-500" />@endif
                                    </button>
                                @endforeach
                            </fieldset>
                        @endif

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            @if (! $answered)
                                @if ($question->isMultipleChoice())
                                    <button type="button" wire:click="submit" class="btn btn-primary" @disabled($selected === null)>Check answer</button>
                                    <button type="button" wire:click="reveal" class="btn btn-ghost">Show answer</button>
                                @else
                                    <button type="button" wire:click="reveal" class="btn btn-primary">{{ $question->type->value === 'viva' ? 'Reveal model answer' : 'Reveal answer' }}</button>
                                    <span class="text-sm text-muted">Formulate your answer first — say it out loud as you would in the viva.</span>
                                @endif
                            @else
                                <button type="button" wire:click="next" class="btn btn-primary">Next question <x-heroicon-m-arrow-right /></button>
                                @if ($question->isMultipleChoice() && $selected)
                                    <span @class(['inline-flex items-center gap-1.5 text-sm font-semibold', 'text-emerald-700' => $selected === $question->correctKey(), 'text-red-600' => $selected !== $question->correctKey()])>
                                        {{ $selected === $question->correctKey() ? 'Correct!' : 'Not quite — the answer is '.$question->correctKey() }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>

                @if ($revealed && $canView)
                    <div class="border-t border-line bg-subtle/60 px-6 py-7 sm:px-8 animate-fade-up">
                        @if (filled((string) $modelAnswer))
                            <h3 class="font-heading text-lg font-semibold text-ink">Model answer</h3>
                            <div class="prose prose-lg prose-clinical mt-3">{!! $modelAnswer !!}</div>
                        @endif
                        @if (filled((string) $explanation))
                            <h3 class="mt-6 font-heading text-lg font-semibold text-ink first:mt-0">Explanation</h3>
                            <div class="prose prose-lg prose-clinical mt-3">{!! $explanation !!}</div>
                        @endif
                        @if (! empty($question->key_points))
                            <div class="callout callout-exam">
                                <p class="callout-title">Key points for the exam</p>
                                <ul class="mt-1 list-disc space-y-1 pl-5">
                                    @foreach ($question->key_points as $point)<li>{{ $point['point'] ?? $point }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                        <x-references :references="$question->references ?? []" class="!mt-8" />
                        @if ($question->successRate() !== null && $question->attempts_count >= 5)
                            <p class="mt-6 text-sm text-muted">{{ $question->successRate() }}% of {{ number_format($question->attempts_count) }} attempts were correct.</p>
                        @endif
                    </div>
                @endif
            </article>
        @endif
    </div>
</div>
