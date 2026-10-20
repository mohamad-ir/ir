@php($social = ! empty($data['show_social']) ? collect(setting('social') ?: [])->filter() : collect())
<div class="grid items-start gap-10 lg:grid-cols-[.8fr_1.2fr] lg:gap-16">
    <div class="relative mx-auto w-full max-w-sm lg:sticky lg:top-28">
        <div class="absolute -inset-3 rounded-[calc(var(--radius-card)*1.5)] bg-gradient-to-br from-accent/20 to-highlight/10 blur-xl" aria-hidden="true"></div>
        <div class="relative overflow-hidden rounded-card bg-subtle shadow-[var(--shadow-lift)] ring-1 ring-line">
            @if ($data['photo'])
                <x-media-img :media="$data['photo']" variant="md" sizes="384px" class="aspect-[4/5] w-full object-cover" :eager="$first" />
            @else
                <div class="grid aspect-[4/5] place-items-center bg-ink text-6xl font-heading text-white/80">{{ collect(explode(' ', preg_replace('/^(Dr|Prof)\.?\s+/i', '', $data['heading'] ?? '')))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</div>
            @endif
        </div>
    </div>
    <div>
        @if (! empty($data['eyebrow']))<p class="eyebrow mb-3">{{ $data['eyebrow'] }}</p>@endif
        <{{ $first ? 'h1' : 'h2' }} class="h-display !text-4xl sm:!text-5xl {{ $tone === 'ink' ? 'text-white' : 'text-ink' }}">{{ $data['heading'] }}</{{ $first ? 'h1' : 'h2' }}>
        @if (! empty($data['credentials']))<p class="mt-3 font-heading text-xl italic text-accent-ink">{{ $data['credentials'] }}</p>@endif
        @if (! empty($data['role']))<p class="mt-2 font-medium text-muted">{{ $data['role'] }}</p>@endif
        <div class="prose prose-lg prose-clinical mt-8 {{ $tone === 'ink' ? 'prose-invert' : '' }}">{!! $data['html'] !!}</div>
        @include('blocks.partials.buttons', ['buttons' => $data['buttons'] ?? [], 'light' => $tone === 'ink', 'class' => 'mt-8'])
        @if ($social->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach ($social as $network => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener me" class="inline-flex items-center gap-2 rounded-full bg-surface px-3.5 py-1.5 text-sm font-medium text-fg ring-1 ring-line transition hover:ring-line-strong">
                        <x-social-icon :network="$network" class="size-4 text-muted" /> {{ \App\Support\Social::label($network) }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
