@extends('account.layout')
@section('account')
    <div class="grid gap-5 sm:grid-cols-3">
        <div class="card p-6"><p class="text-sm text-muted">Saved items</p><p class="mt-2 font-heading text-4xl font-semibold text-ink tabular-nums">{{ $bookmarks->count() }}</p></div>
        <div class="card p-6"><p class="text-sm text-muted">Questions practised</p><p class="mt-2 font-heading text-4xl font-semibold text-ink tabular-nums">{{ $answered }}<span class="text-lg text-muted"> / {{ $totalQuestions }}</span></p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-accent" style="width: {{ $totalQuestions ? min(100, round($answered / $totalQuestions * 100)) : 0 }}%"></div></div></div>
        <div class="card p-6"><p class="text-sm text-muted">Accuracy</p><p class="mt-2 font-heading text-4xl font-semibold text-ink tabular-nums">{{ $accuracy !== null ? $accuracy.'%' : '—' }}</p></div>
    </div>

    <div class="mt-12 grid gap-12 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section>
            <div class="flex items-center justify-between"><h2 class="font-heading text-2xl font-semibold text-ink">Saved for later</h2></div>
            @if ($bookmarks->isEmpty())
                <x-empty-state icon="heroicon-o-bookmark" title="Nothing saved yet" text="Use “Save for later” on any case, article or video." class="mt-6">
                    <a href="{{ route('cases.index') }}" class="btn btn-primary mt-6">Browse cases</a>
                </x-empty-state>
            @else
                <ul class="mt-6 divide-y divide-line rounded-card bg-surface ring-1 ring-line" role="list">
                    @foreach ($bookmarks as $b)
                        <li class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <p class="eyebrow">{{ ['case' => 'OCT case', 'article' => 'Article', 'video' => 'Video', 'question' => 'Question'][$b->bookmarkable_type] ?? '' }}</p>
                                <a href="{{ $b->bookmarkable->url() }}" class="mt-0.5 block truncate font-semibold text-ink hover:text-accent-ink">{{ $b->bookmarkable->title }}</a>
                            </div>
                            <span class="shrink-0 text-xs text-muted">{{ $b->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($recentAttempts->isNotEmpty())
                <h2 class="mt-12 font-heading text-2xl font-semibold text-ink">Recent practice</h2>
                <ul class="mt-6 divide-y divide-line rounded-card bg-surface ring-1 ring-line" role="list">
                    @foreach ($recentAttempts as $a)
                        @continue(! $a->question)
                        <li class="flex items-center gap-4 px-5 py-3.5">
                            @if ($a->is_correct === true)<x-heroicon-s-check-circle class="size-5 shrink-0 text-emerald-600" />
                            @elseif ($a->is_correct === false)<x-heroicon-s-x-circle class="size-5 shrink-0 text-red-500" />
                            @else<x-heroicon-s-eye class="size-5 shrink-0 text-muted" />@endif
                            <a href="{{ route('exam.practice', ['question' => $a->question_id]) }}" class="min-w-0 flex-1 truncate text-[0.9375rem] hover:text-accent-ink">{{ $a->question->title }}</a>
                            <span class="text-xs text-muted">{{ $a->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('exam.practice') }}" class="btn btn-secondary mt-5">Continue practising</a>
            @endif
        </section>
        <aside class="space-y-6">
            <div class="rounded-card bg-accent/[.07] p-5 ring-1 ring-accent/15">
                <p class="font-heading text-lg font-semibold text-ink">Weekly case email</p>
                @if ($subscriber && $subscriber->status->value === 'subscribed')
                    <p class="mt-1 text-sm text-muted">You are subscribed. <a href="{{ route('account.profile') }}" class="link">Manage</a></p>
                @else
                    <livewire:newsletter-form variant="footer" :compact="true" source="account" />
                @endif
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Latest cases</p>
                <div class="mt-3 space-y-1">@foreach ($latestCases as $c)<x-case-card :case="$c" layout="list" />@endforeach</div>
            </div>
        </aside>
    </div>
@endsection
