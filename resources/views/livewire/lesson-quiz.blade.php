<section class="mt-12 overflow-hidden rounded-card bg-surface ring-1 ring-line" aria-labelledby="quiz-heading">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-accent/[.06] px-6 py-4">
        <div>
            <p class="eyebrow">Check your understanding</p>
            <h2 id="quiz-heading" class="mt-1 font-heading text-2xl font-semibold text-ink">Lesson quiz</h2>
        </div>
        <div class="text-right text-sm text-muted">
            {{ count($questions) }} {{ \Illuminate\Support\Str::plural('question', count($questions)) }} · pass mark {{ $lesson->passMark() }}%
            @if ($alreadyPassed && ! $submitted)
                <span class="mt-1 flex items-center justify-end gap-1 font-semibold text-accent-ink"><x-heroicon-s-check-badge class="size-4" /> Passed{{ $best !== null ? ' ('.$best.'%)' : '' }}</span>
            @endif
        </div>
    </div>

    @if ($submitted)
        <div @class(['flex flex-wrap items-center justify-between gap-4 px-6 py-5', 'bg-emerald-50' => $passed, 'bg-highlight/10' => ! $passed]) role="status">
            <div class="flex items-center gap-3">
                @if ($passed)<x-heroicon-s-check-circle class="size-8 text-emerald-600" />@else<x-heroicon-s-arrow-path class="size-8 text-highlight" />@endif
                <div>
                    <p class="font-heading text-2xl font-semibold text-ink">{{ $score }}%</p>
                    <p class="text-sm text-fg/80">{{ $passed ? 'Well done — you passed and the lesson is complete.' : 'Not quite — review the explanations and try again. You need '.$lesson->passMark().'%.' }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="retry" class="btn btn-secondary btn-sm">Try again</button>
                @if ($passed && $nextUrl)<a href="{{ $nextUrl }}" class="btn btn-primary btn-sm">Next lesson <x-heroicon-m-arrow-right /></a>@endif
                @if ($passed && ! $nextUrl)<a href="{{ $lesson->course->urlPath() }}" class="btn btn-primary btn-sm">Back to the course</a>@endif
            </div>
        </div>
    @endif

    <ol class="divide-y divide-line" role="list">
        @foreach ($questions as $qi => $question)
            @php
                $multiple = collect($question['options'])->where('correct', true)->count() > 1;
                $chosen = array_map('intval', (array) ($answers[$qi] ?? []));
                $right = $results[$qi] ?? null;
            @endphp
            <li class="px-6 py-6">
                <div class="flex items-start gap-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-ink/5 text-sm font-semibold tabular-nums text-ink">{{ $qi + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold leading-snug text-ink">{{ $question['question'] }}</p>
                        @if ($multiple)<p class="mt-1 text-xs text-muted">Select all that apply.</p>@endif
                        <div class="mt-4 space-y-2" role="{{ $multiple ? 'group' : 'radiogroup' }}">
                            @foreach ($question['options'] as $oi => $option)
                                @php
                                    $isChosen = in_array($oi, $chosen, true);
                                    $state = ! $submitted ? ($isChosen ? 'chosen' : 'idle') : ($option['correct'] ? ($isChosen ? 'correct' : 'missed') : ($isChosen ? 'wrong' : 'idle'));
                                @endphp
                                <button type="button" wire:click="choose({{ $qi }}, {{ $oi }}, {{ $multiple ? 'true' : 'false' }})" @disabled($submitted)
                                        role="{{ $multiple ? 'checkbox' : 'radio' }}" aria-checked="{{ $isChosen ? 'true' : 'false' }}"
                                        @class([
                                            'flex w-full items-center gap-3 rounded-ui px-4 py-3 text-left text-[0.95rem] ring-1 transition',
                                            'ring-line hover:bg-ink/[.03] hover:ring-line-strong' => $state === 'idle' && ! $submitted,
                                            'ring-line opacity-70' => $state === 'idle' && $submitted,
                                            'bg-accent/10 ring-2 ring-accent' => $state === 'chosen',
                                            'bg-emerald-50 ring-2 ring-emerald-500' => $state === 'correct',
                                            'bg-red-50 ring-2 ring-red-400' => $state === 'wrong',
                                            'bg-emerald-50/40 ring-0 outline-dashed outline-2 -outline-offset-2 outline-emerald-400' => $state === 'missed',
                                        ])>
                                    <span @class(['grid size-5 shrink-0 place-items-center border-2', 'rounded' => $multiple, 'rounded-full' => ! $multiple,
                                        'border-accent bg-accent text-white' => $state === 'chosen', 'border-emerald-500 bg-emerald-500 text-white' => $state === 'correct',
                                        'border-red-400 bg-red-400 text-white' => $state === 'wrong', 'border-line-strong' => $state === 'idle', 'border-emerald-500 text-emerald-600' => $state === 'missed'])>
                                        @if ($state === 'wrong')<x-heroicon-m-x-mark class="size-3.5" />@elseif ($state !== 'idle')<x-heroicon-m-check class="size-3.5" />@endif
                                    </span>
                                    <span class="flex-1">{{ $option['text'] }}</span>
                                    @if ($state === 'missed')<span class="shrink-0 text-xs font-semibold text-emerald-700">Correct answer</span>@endif
                                    @if ($state === 'wrong')<span class="shrink-0 text-xs font-semibold text-red-700">Your answer</span>@endif
                                </button>
                            @endforeach
                        </div>
                        @if ($submitted)
                            <div @class(['mt-4 rounded-ui px-4 py-3 text-sm', 'bg-emerald-50 text-emerald-900' => $right, 'bg-subtle text-fg' => ! $right])>
                                <p class="font-semibold">{{ $right ? 'Correct' : 'Incorrect' }}</p>
                                @if (filled($question['explanation']))<p class="mt-1 leading-relaxed">{{ $question['explanation'] }}</p>@endif
                            </div>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ol>

    @unless ($submitted)
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-6 py-4">
            @error('answers')<p class="field-error">{{ $message }}</p>@else<p class="text-sm text-muted">Your answers are checked when you submit.</p>@enderror
            <button type="button" wire:click="submit" wire:loading.attr="disabled" class="btn btn-primary">Submit answers</button>
        </div>
    @endunless
</section>
