<div class="mx-auto max-w-3xl">
    <x-section-heading :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" align="center" />
    <div class="divide-y divide-line border-y border-line">
        @foreach ($data['items'] ?? [] as $item)
            <details class="group py-5 [&_summary::-webkit-details-marker]:hidden">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-6 text-left font-heading text-lg font-semibold {{ $tone === 'ink' ? 'text-white' : 'text-ink' }}">
                    {{ $item['question'] ?? '' }}
                    <span class="grid size-8 shrink-0 place-items-center rounded-full ring-1 ring-line-strong transition group-open:rotate-45"><x-heroicon-m-plus class="size-4" /></span>
                </summary>
                <div class="mt-3 pr-12 leading-relaxed text-muted">{!! nl2br(e($item['answer'] ?? '')) !!}</div>
            </details>
        @endforeach
    </div>
</div>
