<div class="relative overflow-hidden rounded-card px-6 py-14 text-center sm:px-14 {{ $tone === 'ink' ? '' : 'bg-surface ring-1 ring-line shadow-[var(--shadow-card)]' }}">
    @if ($tone === 'ink')
        <svg class="pointer-events-none absolute inset-x-0 bottom-0 h-40 w-full text-white/[.06]" viewBox="0 0 1200 160" preserveAspectRatio="none" aria-hidden="true"><path d="M0 60c260 0 360 0 470 40s110 50 130 50 40-10 130-50 210-40 470-40" fill="none" stroke="currentColor" stroke-width="2"/><path d="M0 100c300 0 380 10 480 35 60 15 90 20 120 20s60-5 120-20c100-25 180-35 480-35" fill="none" stroke="currentColor" stroke-width="2"/></svg>
    @endif
    <div class="relative mx-auto max-w-2xl">
        @if (! empty($data['eyebrow']))<p class="eyebrow mb-3">{{ $data['eyebrow'] }}</p>@endif
        <h2 class="h-section {{ $tone === 'ink' ? 'text-white' : '' }}">{!! \App\Support\Typography::highlight($data['heading'] ?? '') !!}</h2>
        @if (! empty($data['text']))<p class="mt-4 text-lg text-muted">{{ $data['text'] }}</p>@endif
        @include('blocks.partials.buttons', ['buttons' => $data['buttons'] ?? [], 'light' => $tone === 'ink', 'class' => 'mt-8 justify-center'])
    </div>
</div>
