{{-- Enrolment / purchase panel on the course page. --}}
<div class="card p-6 lg:sticky lg:top-28">
    @if ($canAccess)
        <p class="eyebrow">Your progress</p>
        <div class="mt-3 flex items-baseline justify-between">
            <span class="font-heading text-4xl font-semibold tabular-nums text-ink">{{ $progress }}%</span>
            <span class="text-sm text-muted">{{ count($completed) }} / {{ $lessonCount }} lessons</span>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-accent transition-all" style="width: {{ $progress }}%"></div></div>
        @if ($nextLesson)
            <a href="{{ route('courses.lesson', [$course->slug, $nextLesson->slug]) }}" class="btn btn-primary btn-lg mt-6 w-full">{{ count($completed) ? 'Continue learning' : 'Start the course' }} <x-heroicon-m-arrow-right /></a>
        @endif
        @if ($enrollment?->source === 'membership' || (! $enrollment && $isMember))
            <p class="mt-4 text-center text-xs text-muted">Included with your membership.</p>
        @endif
    @else
        <p class="font-heading text-4xl font-semibold text-ink">{{ $course->priceLabel() }}</p>
        @if (! $course->isFree())
            <p class="mt-1 text-sm text-muted">One-time payment · lifetime access{{ setting('payments.prices_include_tax', true) ? ' · VAT included' : '' }}</p>
        @endif

        @guest
            <a href="{{ route('register', ['redirect' => $course->urlPath()]) }}" class="btn btn-primary btn-lg mt-6 w-full">{{ $course->isFree() ? 'Create free account to enrol' : 'Create an account to buy' }}</a>
            <p class="mt-3 text-center text-sm text-muted">Already registered? <a href="{{ route('login', ['redirect' => $course->urlPath()]) }}" class="link">Sign in</a></p>
        @else
            @if ($course->isFree())
                <form method="post" action="{{ route('courses.enroll', $course->slug) }}" class="mt-6">@csrf
                    <button class="btn btn-primary btn-lg w-full">Enrol for free <x-heroicon-m-arrow-right /></button>
                </form>
            @elseif ($paymentsEnabled)
                <form method="post" action="{{ route('checkout.course', $course->slug) }}" class="mt-6 space-y-3" x-data="{ busy: false }" x-on:submit="busy = true">@csrf
                    @include('checkout.consent')
                    <button class="btn btn-primary btn-lg w-full" x-bind:disabled="busy">
                        <span x-show="!busy">Buy course — {{ $course->priceLabel() }}</span><span x-show="busy" x-cloak>Redirecting to secure payment…</span>
                    </button>
                </form>
            @else
                <p class="mt-6 rounded-ui bg-highlight/10 px-4 py-3 text-sm ring-1 ring-highlight/25">Online enrolment opens soon.</p>
            @endif
        @endguest

        @if (! $course->isFree() && $course->included_in_membership && ! $isMember)
            <div class="mt-6 border-t border-line pt-5 text-sm">
                <p class="font-semibold text-ink">Or get it with membership</p>
                <p class="mt-1 text-muted">This course is included with membership, together with every other course and live webinars.</p>
                <a href="{{ route('membership') }}" class="link-arrow mt-3">See membership options <x-heroicon-m-arrow-right /></a>
            </div>
        @endif
        @if (! $course->isFree() && $paymentsEnabled)
            <p class="mt-5 flex items-center justify-center gap-2 text-xs text-muted"><x-heroicon-m-lock-closed class="size-3.5" /> Secure payment by Stripe · cards, Apple Pay, Google Pay</p>
        @endif
    @endif

    <ul class="mt-6 space-y-2.5 border-t border-line pt-5 text-sm text-fg/85" role="list">
        <li class="flex gap-2.5"><x-heroicon-o-play-circle class="size-5 shrink-0 text-muted" /> {{ $lessonCount }} {{ \Illuminate\Support\Str::plural('lesson', $lessonCount) }}@if($course->durationLabel()) · {{ $course->durationLabel() }} of content @endif</li>
        @if ($course->level)<li class="flex gap-2.5"><x-heroicon-o-signal class="size-5 shrink-0 text-muted" /> {{ $course->level->getLabel() }} level</li>@endif
        <li class="flex gap-2.5"><x-heroicon-o-clock class="size-5 shrink-0 text-muted" /> Self-paced — learn on any device</li>
        <li class="flex gap-2.5"><x-heroicon-o-check-badge class="size-5 shrink-0 text-muted" /> Progress saved automatically</li>
    </ul>
</div>
