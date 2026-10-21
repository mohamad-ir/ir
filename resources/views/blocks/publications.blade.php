@php($entries = $data['entries'] ?? collect())
@php($years = $entries->pluck('start_year')->filter()->unique()->sortDesc()->values())
<div x-data="{ type: 'all', year: 'all' }">
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            @if (! empty($data['heading']))<h2 class="h-section">{{ $data['heading'] }}</h2>@endif
            @if (! empty($data['intro']))<p class="mt-3 max-w-2xl text-muted">{{ $data['intro'] }}</p>@endif
        </div>
        @if (! empty($data['searchable']) && $entries->count() > 5)
            <div class="flex flex-wrap gap-2">
                <select x-model="type" class="input !w-auto !py-2 text-sm" aria-label="Filter by type">
                    <option value="all">All types</option><option value="publication">Journal articles</option><option value="book">Books &amp; chapters</option>
                </select>
                <select x-model="year" class="input !w-auto !py-2 text-sm" aria-label="Filter by year">
                    <option value="all">All years</option>
                    @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                </select>
            </div>
        @endif
    </div>
    <ol class="divide-y divide-line border-t border-line" role="list">
        @forelse ($entries as $entry)
            <li class="grid gap-2 py-5 sm:grid-cols-[5rem_1fr] sm:gap-6" x-show="(type === 'all' || type === @js($entry->type->value)) && (year === 'all' || year === @js((string) $entry->start_year))">
                <span class="font-heading text-lg font-semibold tabular-nums text-accent-ink">{{ $entry->start_year }}</span>
                <div>
                    <p class="font-semibold leading-snug text-ink">
                        @if ($entry->link())<a href="{{ $entry->link() }}" target="_blank" rel="noopener" class="hover:text-accent-ink transition">{{ $entry->title }}</a>@else{{ $entry->title }}@endif
                        @if ($entry->is_highlighted)<span class="chip chip-highlight ml-1 align-middle">Key paper</span>@endif
                    </p>
                    @if ($entry->authors)<p class="mt-1 text-sm text-muted">{{ $entry->authors }}</p>@endif
                    @if ($entry->subtitle)<p class="mt-0.5 text-sm italic text-fg/75">{{ $entry->subtitle }}</p>@endif
                    <div class="mt-2 flex gap-3 text-sm">
                        @if ($entry->doi)<a class="link" href="https://doi.org/{{ $entry->doi }}" target="_blank" rel="noopener">DOI</a>@endif
                        @if ($entry->pmid)<a class="link" href="https://pubmed.ncbi.nlm.nih.gov/{{ $entry->pmid }}/" target="_blank" rel="noopener">PubMed</a>@endif
                    </div>
                </div>
            </li>
        @empty
            <li class="py-6 text-muted">Publications can be added under Content › Profile &amp; CV.</li>
        @endforelse
    </ol>
</div>
