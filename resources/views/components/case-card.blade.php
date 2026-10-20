@props(['case', 'layout' => 'card', 'hideAnswer' => true])
@php($cover = $case->coverImage())
@if ($layout === 'list')
    <a href="{{ $case->urlPath() }}" {{ $attributes->merge(['class' => 'group flex gap-5 rounded-card p-3 -mx-3 transition hover:bg-ink/[.035]']) }}>
        <div class="viewer-frame aspect-[4/3] w-32 shrink-0 sm:w-40">
            @if ($cover)
                <x-media-img :media="$cover" variant="thumb" sizes="160px" class="size-full object-cover opacity-95 transition duration-500 group-hover:scale-[1.04]" />
            @endif
        </div>
        <div class="min-w-0 py-1">
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($case->topics->take(2) as $topic)
                    <span class="chip-accent chip">{{ $topic->abbreviation ?: $topic->name }}</span>
                @endforeach
                <x-difficulty-badge :level="$case->difficulty" />
            </div>
            <h3 class="mt-2 font-heading text-lg font-semibold leading-snug text-ink group-hover:text-accent-ink transition">{{ $case->title }}</h3>
            @if ($case->summary)
                <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $case->summary }}</p>
            @endif
        </div>
    </a>
@else
    <article {{ $attributes->merge(['class' => 'group card-interactive relative flex flex-col overflow-hidden']) }}>
        <div class="viewer-frame !rounded-none aspect-[16/10]">
            @if ($cover)
                <x-media-img :media="$cover" variant="md" sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw" class="size-full object-cover opacity-95 transition duration-700 group-hover:scale-[1.035] group-hover:opacity-100" />
            @else
                <div class="grid size-full place-items-center text-white/30"><x-heroicon-o-photo class="size-10" /></div>
            @endif
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/55 to-transparent"></div>
            <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                @if ($case->is_case_of_week)
                    <span class="inline-flex items-center gap-1 rounded-full bg-highlight px-2.5 py-0.5 text-[0.7rem] font-bold uppercase tracking-wider text-highlight-contrast">Case of the week</span>
                @endif
            </div>
            <div class="absolute bottom-3 left-3 flex flex-wrap gap-1.5">
                @foreach ($case->modalities->take(3) as $modality)
                    <span class="rounded bg-black/55 px-1.5 py-0.5 text-[0.68rem] font-semibold tracking-wide text-white/90 backdrop-blur">{{ $modality->label() }}</span>
                @endforeach
            </div>
        </div>
        <div class="flex flex-1 flex-col p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($case->topics->take(2) as $topic)
                        <span class="chip chip-accent">{{ $topic->abbreviation ?: $topic->name }}</span>
                    @endforeach
                </div>
                <x-difficulty-badge :level="$case->difficulty" class="shrink-0" />
            </div>
            <h3 class="mt-3 font-heading text-[1.3rem] font-semibold leading-snug text-ink">
                <a href="{{ $case->urlPath() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $case->title }}</a>
            </h3>
            @if ($case->summary)
                <p class="mt-2 line-clamp-3 text-[0.9375rem] leading-relaxed text-muted">{{ $case->summary }}</p>
            @endif
            <div class="mt-auto flex items-center justify-between pt-5 text-sm">
                <span class="text-muted">{{ $case->published_at?->format('j M Y') }}</span>
                <span class="link-arrow">Study case <x-heroicon-m-arrow-right /></span>
            </div>
        </div>
    </article>
@endif
