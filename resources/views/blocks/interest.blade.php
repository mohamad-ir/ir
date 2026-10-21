<x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" />
@if (! empty($data['items']))
    <div class="mb-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($data['items'] as $item)
            <div class="card relative flex flex-col p-6">
                <span class="chip chip-highlight self-start">Coming soon</span>
                <h3 class="h-card mt-4 !text-lg">{{ $item['title'] ?? '' }}</h3>
                @if (! empty($item['meta']))<p class="mt-1 text-sm font-medium text-accent-ink">{{ $item['meta'] }}</p>@endif
                @if (! empty($item['text']))<p class="mt-3 text-sm leading-relaxed text-muted">{{ $item['text'] }}</p>@endif
            </div>
        @endforeach
    </div>
@endif
<div class="card mx-auto max-w-2xl p-6 sm:p-8">
    <p class="font-heading text-xl font-semibold text-ink">Register your interest</p>
    <p class="mt-1 text-sm text-muted">We’ll email you once when enrolment opens. No other messages unless you choose them.</p>
    <livewire:newsletter-form :interests="[$data['interest'] ?? 'courses']" source="interest" :compact="true" :key="'interest-'.$index" />
</div>
