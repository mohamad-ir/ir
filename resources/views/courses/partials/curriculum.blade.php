{{-- $curriculum, $course, $completed, $canAccess, optional $current (lesson) and $compact --}}
@php $compact = $compact ?? false; @endphp
<div class="space-y-4">
    @foreach ($curriculum as $group)
        @continue($group['lessons']->isEmpty() && ! $group['section'])
        <div @class(['overflow-hidden rounded-card bg-surface ring-1 ring-line' => ! $compact])>
            @if ($group['section'])
                <div @class(['flex items-baseline justify-between gap-4', 'border-b border-line px-5 py-4' => ! $compact, 'px-2 pb-1 pt-3' => $compact])>
                    <p @class(['font-semibold text-ink', 'text-sm' => $compact])>{{ $group['section']->title }}</p>
                    <span class="shrink-0 text-xs text-muted">{{ $group['lessons']->count() }} {{ \Illuminate\Support\Str::plural('lesson', $group['lessons']->count()) }}</span>
                </div>
            @endif
            <ol role="list" @class(['divide-y divide-line' => ! $compact])>
                @foreach ($group['lessons'] as $lesson)
                    @php
                        $done = in_array($lesson->id, $completed, true);
                        $open = $canAccess || $lesson->is_preview;
                        $isCurrent = isset($current) && $current->id === $lesson->id;
                    @endphp
                    <li>
                        <a href="{{ route('courses.lesson', [$course->slug, $lesson->slug]) }}"
                           @class(['flex items-center gap-3 transition', 'px-5 py-3.5 hover:bg-ink/[.03]' => ! $compact, 'rounded-ui px-2 py-2 text-sm hover:bg-ink/5' => $compact, 'bg-accent/10 font-semibold' => $isCurrent])
                           @if($isCurrent) aria-current="page" @endif>
                            <span class="grid size-6 shrink-0 place-items-center">
                                @if ($done)
                                    <x-heroicon-s-check-circle class="size-5 text-accent" />
                                @elseif (! $open)
                                    <x-heroicon-o-lock-closed class="size-4 text-muted" />
                                @elseif ($lesson->video_provider)
                                    <x-heroicon-o-play-circle class="size-5 text-muted" />
                                @else
                                    <x-heroicon-o-document-text class="size-5 text-muted" />
                                @endif
                            </span>
                            <span class="min-w-0 flex-1 {{ $open ? 'text-ink' : 'text-muted' }}">{{ $lesson->title }}</span>
                            @if ($lesson->is_preview && ! $canAccess)
                                <span class="chip chip-accent shrink-0 !text-[0.7rem]">Free preview</span>
                            @endif
                            @if ($lesson->duration_minutes)
                                <span class="shrink-0 text-xs tabular-nums text-muted">{{ $lesson->duration_minutes }} min</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    @endforeach
</div>
