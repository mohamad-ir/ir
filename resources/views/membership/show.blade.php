@extends('layouts.app')
@section('content')
<x-banner-slider placement="membership" />
    <section class="relative overflow-hidden border-b border-line bg-surface">
        <div class="bg-retina-lines absolute inset-0 opacity-70" aria-hidden="true"></div>
        <div class="container-site relative py-14 text-center sm:py-20">
            <p class="eyebrow">Membership</p>
            <h1 class="h-display mx-auto mt-3 max-w-3xl">{{ setting('membership.heading', 'Become a member') }}</h1>
            @if (setting('membership.intro'))<p class="lead mx-auto mt-5 max-w-2xl">{{ setting('membership.intro') }}</p>@endif
            @if ($subscription)
                <p class="mt-6 inline-flex items-center gap-2 rounded-full bg-accent/10 px-4 py-2 text-sm font-medium text-accent-ink"><x-heroicon-s-check-badge class="size-5" /> You are a member{{ $subscription->plan ? ' — '.$subscription->plan->name : '' }}</p>
            @endif
        </div>
    </section>

    <div class="container-site section">
        @if ($plans->isEmpty())
            <x-empty-state icon="heroicon-o-sparkles" title="Membership opens soon" text="Subscribe to the newsletter to be told as soon as it launches.">
                <a href="{{ route('newsletter.show') }}" class="btn btn-primary mt-6">Notify me</a>
            </x-empty-state>
        @else
            @include('membership.plans', ['plans' => $plans, 'subscription' => $subscription, 'paymentsEnabled' => $paymentsEnabled])
        @endif

        @if ($included)
            <div class="mx-auto mt-20 max-w-4xl">
                <h2 class="h-section text-center !text-3xl">What membership includes</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    @foreach (['courses' => ['academic-cap', 'courses'], 'webinars' => ['video-camera', 'upcoming live webinars'], 'cases' => ['eye', 'members-only case discussions'], 'videos' => ['play-circle', 'members-only videos'], 'articles' => ['document-text', 'members-only articles'], 'questions' => ['clipboard-document-check', 'members-only exam questions']] as $key => [$icon, $label])
                        @continue(empty($included[$key]))
                        <div class="card flex items-center gap-4 p-5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-ui bg-accent/10 text-accent-ink"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5.5" /></span>
                            <span><span class="block font-heading text-2xl font-semibold text-ink">{{ $included[$key] }}</span><span class="text-sm text-muted">{{ $label }}</span></span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($courses->isNotEmpty())
            <div class="mt-20">
                <x-section-heading eyebrow="Included" heading="Courses in your membership" action-label="All courses" :action-url="route('courses.index')" />
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($courses as $course)<x-course-card :course="$course" :is-member="(bool) $subscription" />@endforeach
                </div>
            </div>
        @endif

        @if ($faq->isNotEmpty())
            <div class="mx-auto mt-20 max-w-3xl">
                <h2 class="h-section text-center !text-3xl">Frequently asked questions</h2>
                <div class="mt-8 divide-y divide-line rounded-card bg-surface ring-1 ring-line">
                    @foreach ($faq as $item)
                        <details class="group px-5 py-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink [&::-webkit-details-marker]:hidden">{{ $item['question'] }}<x-heroicon-m-chevron-down class="size-5 shrink-0 transition group-open:rotate-180" /></summary>
                            <p class="mt-3 text-[0.95rem] leading-relaxed text-muted">{{ $item['answer'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
