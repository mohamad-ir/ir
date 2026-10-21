@props(['light' => false, 'size' => 'md'])
@php
    $logo = \App\Models\Media::find(setting($light ? 'branding.logo_dark_id' : 'branding.logo_id')) ?? ($light ? null : null);
    $name = setting('branding.logo_text') ?: setting('site.name');
@endphp
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'group inline-flex items-center gap-2.5 shrink-0']) }} aria-label="{{ setting('site.name') }} — home">
    @if ($logo)
        <img src="{{ $logo->url() }}" alt="{{ setting('site.name') }}" class="{{ $size === 'lg' ? 'h-11' : 'h-9' }} w-auto">
    @else
        <svg viewBox="0 0 32 32" class="{{ $size === 'lg' ? 'size-10' : 'size-8' }} shrink-0" aria-hidden="true">
            <rect width="32" height="32" rx="8" fill="{{ $light ? '#ffffff' : 'var(--color-ink)' }}" fill-opacity="{{ $light ? '.1' : '1' }}"/>
            <path d="M4.5 12.2c5.2 0 7 .1 8.6 3.2 1 1.9 1.7 3.1 2.9 3.1s1.9-1.2 2.9-3.1c1.6-3.1 3.4-3.2 8.6-3.2" fill="none" stroke="var(--color-accent)" stroke-width="2.1" stroke-linecap="round"/>
            <path d="M4.5 17.4c4.6 0 6.4.4 8.4 2.5 1.2 1.3 2 1.8 3.1 1.8s1.9-.5 3.1-1.8c2-2.1 3.8-2.5 8.4-2.5" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M4.5 22.6h23" stroke="var(--color-highlight)" stroke-opacity=".9" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span class="flex flex-col leading-none">
            <span class="font-heading text-[1.28rem] font-semibold tracking-tight {{ $light ? 'text-white' : 'text-ink' }}">{{ $name }}</span>
            @if ($size === 'lg' && setting('site.tagline'))
                <span class="mt-1 text-xs {{ $light ? 'text-white/60' : 'text-muted' }}">{{ setting('site.tagline') }}</span>
            @endif
        </span>
    @endif
</a>
