@php
    $layout = $data['layout'] ?? 'split';
    $image = $data['image'] ?? null;
    $buttons = $data['buttons'] ?? [];
    $heading = \App\Support\Typography::highlight($data['heading'] ?? '');
    $tag = $first ? 'h1' : 'h2';
@endphp
@if ($layout === 'background' && $image)
    <div class="relative -mx-5 overflow-hidden sm:-mx-8 lg:mx-0 lg:rounded-card">
        <x-media-img :media="$image" variant="lg" sizes="100vw" class="absolute inset-0 size-full object-cover" :eager="$first" />
        <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/85 to-ink/30"></div>
        <div class="relative max-w-2xl px-6 py-20 text-white sm:px-12 lg:py-28">
            @if (! empty($data['eyebrow']))<p class="eyebrow !text-white/70 mb-4">{{ $data['eyebrow'] }}</p>@endif
            <{{ $tag }} class="h-display text-white [&_span]:!text-[color-mix(in_oklab,var(--color-accent)_45%,white)]">{!! $heading !!}</{{ $tag }}>
            @if (! empty($data['subheading']))<p class="mt-6 text-lg leading-relaxed text-white/80 sm:text-xl">{{ $data['subheading'] }}</p>@endif
            @include('blocks.partials.buttons', ['buttons' => $buttons, 'light' => true, 'class' => 'mt-9'])
        </div>
    </div>
@else
    <div @class(['grid items-center gap-12 lg:gap-16', 'lg:grid-cols-[1.08fr_.92fr]' => $layout === 'split' && $image, 'text-center' => $layout === 'centered'])>
        <div @class(['animate-fade-up', 'mx-auto max-w-3xl' => $layout === 'centered' || ! $image])>
            @if (! empty($data['eyebrow']))
                <p @class(['mb-5 inline-flex items-center gap-2 rounded-full py-1 pl-1 pr-3.5 text-sm font-medium', 'bg-white/10 text-white/85' => $tone === 'ink', 'bg-accent/10 text-accent-ink' => $tone !== 'ink'])>
                    <span class="grid size-6 place-items-center rounded-full {{ $tone === 'ink' ? 'bg-white/15' : 'bg-accent text-accent-contrast' }}"><x-heroicon-m-eye class="size-3.5" /></span>
                    {{ $data['eyebrow'] }}
                </p>
            @endif
            <{{ $tag }} class="h-display {{ $tone === 'ink' ? 'text-white' : 'text-ink' }}">{!! $heading !!}</{{ $tag }}>
            @if (! empty($data['subheading']))
                <p @class(['mt-6 text-lg leading-relaxed sm:text-xl', 'text-white/75' => $tone === 'ink', 'text-muted' => $tone !== 'ink', 'mx-auto max-w-2xl' => $layout === 'centered'])>{{ $data['subheading'] }}</p>
            @endif

            @if (! empty($data['show_search']))
                <form action="{{ route('search') }}" method="get" @class(['mt-9 flex max-w-xl items-center gap-2 rounded-full bg-surface p-1.5 pl-5 shadow-[var(--shadow-lift)] ring-1 ring-line', 'mx-auto' => $layout === 'centered'])>
                    <x-heroicon-o-magnifying-glass class="size-5 shrink-0 text-muted" />
                    <label for="hero-search" class="sr-only">Search</label>
                    <input id="hero-search" type="search" name="q" placeholder="Search CSR, VKH, macular hole…" class="w-full border-0 bg-transparent py-2 text-fg placeholder:text-muted/70 focus:ring-0 focus:outline-none">
                    <button class="btn btn-primary !rounded-full">Search</button>
                </form>
            @endif

            @include('blocks.partials.buttons', ['buttons' => $buttons, 'light' => $tone === 'ink', 'class' => 'mt-9 '.($layout === 'centered' ? 'justify-center' : '')])

            @if (! empty($data['highlights']))
                <ul @class(['mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm', 'justify-center' => $layout === 'centered', 'text-white/70' => $tone === 'ink', 'text-muted' => $tone !== 'ink']) role="list">
                    @foreach ($data['highlights'] as $chip)
                        <li class="inline-flex items-center gap-2"><x-heroicon-m-check-badge class="size-5 text-accent" />{{ $chip }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($image && $layout !== 'centered')
            <div class="relative animate-fade-up [animation-delay:120ms]">
                <div class="absolute -inset-4 -z-0 rounded-[calc(var(--radius-card)*1.6)] bg-gradient-to-br from-accent/15 via-transparent to-highlight/15 blur-2xl" aria-hidden="true"></div>
                <div class="relative overflow-hidden rounded-card shadow-[var(--shadow-pop)] ring-1 ring-black/5">
                    <x-media-img :media="$image" variant="lg" sizes="(min-width: 1024px) 560px, 100vw" class="aspect-[4/5] w-full object-cover sm:aspect-[5/4] lg:aspect-[4/5]" :eager="$first" />
                </div>
            </div>
        @elseif ($image && $layout === 'centered')
            <div class="mx-auto mt-4 max-w-5xl overflow-hidden rounded-card shadow-[var(--shadow-pop)]">
                <x-media-img :media="$image" variant="lg" sizes="100vw" class="w-full object-cover" :eager="$first" />
            </div>
        @endif
    </div>
@endif
