@php
    $image = $data['image'] ?? null;
    $right = ($data['image_position'] ?? 'right') === 'right';
    $style = $data['image_style'] ?? 'rounded';
@endphp
<div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
    <div class="{{ $right ? 'lg:order-1' : 'lg:order-2' }} reveal-on-scroll">
        @if (! empty($data['eyebrow']))<p class="eyebrow mb-3">{{ $data['eyebrow'] }}</p>@endif
        @if (! empty($data['heading']))<h2 class="h-section">{!! \App\Support\Typography::highlight($data['heading']) !!}</h2>@endif
        @if (filled((string) ($data['html'] ?? '')))
            <div class="prose prose-lg prose-clinical mt-6 {{ $tone === 'ink' ? 'prose-invert' : '' }}">{!! $data['html'] !!}</div>
        @endif
        @include('blocks.partials.buttons', ['buttons' => $data['buttons'] ?? [], 'light' => $tone === 'ink', 'class' => 'mt-8'])
    </div>
    <div class="{{ $right ? 'lg:order-2' : 'lg:order-1' }} reveal-on-scroll">
        @if ($image)
            <figure @class([
                'overflow-hidden rounded-card',
                'shadow-[var(--shadow-lift)]' => $style === 'rounded',
                'bg-surface p-3 ring-1 ring-line shadow-[var(--shadow-card)]' => $style === 'framed',
                'viewer-frame p-4' => $style === 'dark',
            ])>
                <x-media-img :media="$image" variant="lg" sizes="(min-width: 1024px) 560px, 100vw" class="w-full {{ $style === 'rounded' ? '' : 'rounded-ui' }}" />
                @if ($image->caption)
                    <figcaption class="mt-3 px-1 text-sm {{ $style === 'dark' ? 'text-white/60' : 'text-muted' }}">{{ $image->caption }}</figcaption>
                @endif
            </figure>
        @endif
    </div>
</div>
