@props(['references' => []])
@php($refs = collect($references)->filter(fn ($r) => filled($r['citation'] ?? null))->values())
@if ($refs->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'mt-14 border-t border-line pt-8']) }} aria-labelledby="references-heading" id="references" data-toc-section>
        <h2 id="references-heading" class="font-heading text-xl font-semibold text-ink">References</h2>
        <ol class="mt-5 space-y-3 text-[0.9rem] leading-relaxed text-fg/85" role="list">
            @foreach ($refs as $i => $ref)
                <li id="ref-{{ $i + 1 }}" class="flex gap-3 scroll-mt-28 target:rounded-ui target:bg-highlight/10">
                    <span class="w-6 shrink-0 text-right font-semibold tabular-nums text-muted">{{ $i + 1 }}.</span>
                    <span>
                        {{ $ref['citation'] }}
                        @if (! empty($ref['doi']))
                            <a href="https://doi.org/{{ ltrim(preg_replace('~^https?://(dx\.)?doi\.org/~', '', $ref['doi']), '/') }}" target="_blank" rel="noopener" class="link ml-1 text-sm">DOI</a>
                        @endif
                        @if (! empty($ref['pmid']))
                            <a href="https://pubmed.ncbi.nlm.nih.gov/{{ $ref['pmid'] }}/" target="_blank" rel="noopener" class="link ml-1 text-sm">PubMed</a>
                        @endif
                        @if (! empty($ref['url']) && empty($ref['doi']) && empty($ref['pmid']))
                            <a href="{{ $ref['url'] }}" target="_blank" rel="noopener" class="link ml-1 text-sm">Link</a>
                        @endif
                    </span>
                </li>
            @endforeach
        </ol>
    </section>
@endif
