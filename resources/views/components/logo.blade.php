@props(['light' => false, 'size' => 'md'])
{{--
    Site logo. $light = the logo sits on a dark background (dark header/footer, sign-in page).
    In dark mode the page background is dark too, so the "dark backgrounds" version is swapped in.
    Without that version, the main logo is shown as a white silhouette so it stays readable.
--}}
@php
    $brand = app(\App\Services\Branding\BrandingService::class);
    $main = $brand->logo('light');
    $onDark = $brand->logo('dark');
    $step = ['sm' => 0, 'md' => 1, 'lg' => 2][setting('branding.logo_height', 'md')] ?? 1;
    $height = ['h-7', 'h-9', 'h-11', 'h-12'][$step + ($size === 'lg' ? 1 : 0)];
    $img = "{$height} w-auto max-w-[11rem] object-contain object-left sm:max-w-[16rem]";
    $alt = $brand->logoAlt();
@endphp
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'group inline-flex items-center gap-2.5 shrink-0']) }} aria-label="{{ $brand->name() }} — home">
    @if ($light && ($onDark || $main))
        <img src="{{ ($onDark ?? $main)->url() }}" alt="{{ $alt }}" @class([$img, 'brightness-0 invert' => ! $onDark])>
    @elseif ($main)
        <img src="{{ $main->url() }}" alt="{{ $alt }}" @class([$img, 'dark:hidden' => $onDark, 'dark:brightness-0 dark:invert' => ! $onDark])>
        @if ($onDark)
            <img src="{{ $onDark->url() }}" alt="{{ $alt }}" class="{{ $img }} hidden dark:block">
        @endif
    @else
        <svg viewBox="0 0 32 32" class="{{ $size === 'lg' ? 'size-10' : 'size-8' }} shrink-0" aria-hidden="true">
            <rect width="32" height="32" rx="8" fill="{{ $light ? '#ffffff' : 'var(--color-logo-tile)' }}" fill-opacity="{{ $light ? '.1' : '1' }}"/>
            <path d="M4.5 12.2c5.2 0 7 .1 8.6 3.2 1 1.9 1.7 3.1 2.9 3.1s1.9-1.2 2.9-3.1c1.6-3.1 3.4-3.2 8.6-3.2" fill="none" stroke="var(--color-accent)" stroke-width="2.1" stroke-linecap="round"/>
            <path d="M4.5 17.4c4.6 0 6.4.4 8.4 2.5 1.2 1.3 2 1.8 3.1 1.8s1.9-.5 3.1-1.8c2-2.1 3.8-2.5 8.4-2.5" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M4.5 22.6h23" stroke="var(--color-highlight)" stroke-opacity=".9" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span class="flex flex-col leading-none">
            <span class="font-heading text-[1.28rem] font-semibold tracking-tight {{ $light ? 'text-white' : 'text-ink' }}">{{ $brand->displayTitle() }}</span>
            @if ($size === 'lg' && setting('site.tagline'))
                <span class="mt-1 text-xs {{ $light ? 'text-white/60' : 'text-muted' }}">{{ setting('site.tagline') }}</span>
            @endif
        </span>
    @endif
</a>
