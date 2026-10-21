@extends('account.layout')
@section('account')
    @if ($certificates->isNotEmpty())
        <section class="mb-10">
            <h2 class="font-heading text-2xl font-semibold text-ink">My certificates</h2>
            <ul class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($certificates as $certificate)
                    <li class="card flex items-center gap-4 p-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-highlight/15 text-highlight"><x-heroicon-s-trophy class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $certificate->verifyUrl() }}" class="block truncate font-semibold text-ink hover:text-accent-ink">{{ $certificate->course_title }}</a>
                            <p class="text-xs text-muted">{{ $certificate->issued_at->format('j M Y') }} · {{ $certificate->code }}</p>
                        </div>
                        <a href="{{ $certificate->downloadUrl() }}" class="btn btn-secondary btn-sm" aria-label="Download PDF certificate for {{ $certificate->course_title }}"><x-heroicon-m-arrow-down-tray /></a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($enrollments->isNotEmpty() || $webinars->isNotEmpty())
        <div class="mb-12 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <section>
                <h2 class="font-heading text-2xl font-semibold text-ink">My courses</h2>
                @if ($enrollments->isEmpty())
                    <p class="mt-4 text-muted">You are not enrolled in a course yet. <a href="{{ route('courses.index') }}" class="link">Browse courses</a></p>
                @else
                    <ul class="mt-5 space-y-3" role="list">
                        @foreach ($enrollments as $e)
                            @php $resume = $e->lastLesson ?? $e->course->orderedLessons()->first(); @endphp
                            <li class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $e->course->urlPath() }}" class="font-heading text-lg font-semibold text-ink hover:text-accent-ink">{{ $e->course->title }}</a>
                                    <div class="mt-2 flex items-center gap-3">
                                        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-line"><span class="block h-full rounded-full bg-accent" style="width: {{ $e->progress }}%"></span></span>
                                        <span class="text-sm tabular-nums text-muted">{{ $e->progress }}%</span>
                                    </div>
                                </div>
                                @if ($resume)
                                    <a href="{{ route('courses.lesson', [$e->course->slug, $resume->slug]) }}" class="btn btn-secondary btn-sm shrink-0">{{ $e->completed_at ? 'Review' : ($e->progress ? 'Continue' : 'Start') }} <x-heroicon-m-arrow-right /></a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <aside>
                <h2 class="font-heading text-2xl font-semibold text-ink">Upcoming webinars</h2>
                @if ($webinars->isEmpty())
                    <p class="mt-4 text-sm text-muted">No registrations. <a href="{{ route('webinars.index') }}" class="link">See webinars</a></p>
                @else
                    <ul class="mt-5 space-y-3" role="list">
                        @foreach ($webinars as $r)
                            <li class="card p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-accent-ink">{{ $r->webinar->displayStart('D j M, H:i') }}</p>
                                <a href="{{ $r->webinar->urlPath() }}" class="mt-1 block font-semibold leading-snug text-ink hover:text-accent-ink">{{ $r->webinar->title }}</a>
                                @if ($r->webinar->joinWindowOpen())<a href="{{ route('webinars.join', $r->webinar->slug) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm mt-3">Join now</a>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>
        </div>
    @endif

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
            <div class="rounded-card bg-surface p-5 ring-1 ring-line">
                <p class="font-heading text-lg font-semibold text-ink">Membership</p>
                @if ($subscription)
                    <p class="mt-1 text-sm text-muted">{{ $subscription->plan?->name ?? 'Member' }} · {{ $subscription->status->getLabel() }}</p>
                    <a href="{{ route('account.billing') }}" class="link-arrow mt-3 text-sm">Manage membership <x-heroicon-m-arrow-right /></a>
                @else
                    <p class="mt-1 text-sm text-muted">Unlock every course, live webinars and the full library.</p>
                    <a href="{{ route('membership') }}" class="link-arrow mt-3 text-sm">See membership options <x-heroicon-m-arrow-right /></a>
                @endif
            </div>
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
