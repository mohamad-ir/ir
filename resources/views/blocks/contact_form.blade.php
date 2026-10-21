<div class="grid gap-10 lg:grid-cols-[.9fr_1.1fr] lg:gap-16">
    <div>
        <h2 class="h-section {{ $tone === 'ink' ? 'text-white' : '' }}">{{ $data['heading'] ?? 'Get in touch' }}</h2>
        @if (! empty($data['text']))<p class="mt-4 text-lg leading-relaxed text-muted">{{ $data['text'] }}</p>@endif
        <ul class="mt-8 space-y-4 text-[0.9375rem]" role="list">
            @foreach (['Scientific & research collaboration', 'Invitations to speak or teach', 'Educational partnerships'] as $line)
                <li class="flex items-start gap-3"><x-heroicon-m-check-circle class="mt-0.5 size-5 shrink-0 text-accent" /> {{ $line }}</li>
            @endforeach
        </ul>
        <div class="mt-8 rounded-card bg-highlight/10 p-5 text-sm leading-relaxed text-fg/85 ring-1 ring-highlight/25">
            <p class="font-semibold text-ink">Not for patient enquiries</p>
            <p class="mt-1">This form cannot be used for medical advice, appointments or second opinions. If you have an urgent eye problem please contact your local eye casualty or emergency services.</p>
        </div>
    </div>
    <div class="card p-6 sm:p-8">
        <livewire:contact-form :type="$data['default_type'] ?? null" :key="'contact-'.$index" />
    </div>
</div>
