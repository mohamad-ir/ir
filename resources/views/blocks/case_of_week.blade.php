@php($case = $data['case'] ?? null)
@if ($case)
    @php($cover = $case->coverImage())
    <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
        <a href="{{ $case->urlPath() }}" class="group viewer-frame relative block aspect-[4/3] ring-1 ring-white/10">
            @if ($cover)
                <x-media-img :media="$cover" variant="lg" sizes="(min-width: 1024px) 600px, 100vw" class="size-full object-cover transition duration-700 group-hover:scale-[1.03]" />
            @endif
            <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-highlight px-3 py-1 text-xs font-bold uppercase tracking-wider text-highlight-contrast">
                <x-heroicon-m-sparkles class="size-3.5" /> {{ $case->case_of_week_date ? 'Week of '.$case->case_of_week_date->format('j M') : 'This week' }}
            </span>
            <span class="absolute bottom-4 left-4 flex gap-1.5">
                @foreach ($case->modalities as $m)
                    <span class="rounded bg-black/60 px-2 py-0.5 text-xs font-semibold text-white/90 backdrop-blur">{{ $m->label() }}</span>
                @endforeach
            </span>
        </a>
        <div>
            <p class="eyebrow">{{ $data['eyebrow'] ?? 'Case of the Week' }}</p>
            <h2 class="h-section mt-3">{{ ($data['heading'] ?? null) ?: $case->title }}</h2>
            @if ($case->presenting_complaint || $case->summary)
                <p class="mt-5 text-lg leading-relaxed text-muted">{{ $case->presenting_complaint ?: $case->summary }}</p>
            @endif
            <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                @if ($case->patient_age)
                    <div><dt class="text-muted">Patient</dt><dd class="font-semibold">{{ $case->patient_age }}{{ $case->patient_sex ? ', '.$case->patient_sex : '' }}</dd></div>
                @endif
                <div><dt class="text-muted">Difficulty</dt><dd class="font-semibold">{{ $case->difficulty?->getLabel() }}</dd></div>
                @if ($case->guesses_count > 4)
                    <div><dt class="text-muted">Answered correctly</dt><dd class="font-semibold">{{ round($case->correct_guesses_count / max(1, $case->guesses_count) * 100) }}%</dd></div>
                @endif
            </dl>
            @if (! empty($data['intro']))
                <p class="mt-6 border-l-2 border-accent pl-4 text-[0.9375rem] text-muted">{{ $data['intro'] }}</p>
            @endif
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ $case->urlPath() }}" class="btn btn-lg {{ $tone === 'ink' ? 'btn-light' : 'btn-primary' }}">{{ ($data['button_label'] ?? null) ?: 'Attempt this case' }} <x-heroicon-m-arrow-right /></a>
                <a href="{{ route('cases.index', ['cotw' => 1]) }}" class="btn btn-lg {{ $tone === 'ink' ? 'btn-outline-light' : 'btn-secondary' }}">Past cases</a>
            </div>
        </div>
    </div>
@else
    <x-section-heading :eyebrow="$data['eyebrow'] ?? 'Case of the Week'" heading="A new case is coming soon" intro="Subscribe to receive each week’s case as soon as it is published." />
@endif
