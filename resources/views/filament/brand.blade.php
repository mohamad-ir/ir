{{-- Dashboard logo: follows the dashboard's own light/dark mode. --}}
@php
    $brand = app(\App\Services\Branding\BrandingService::class);
    $main = $brand->logo('light');
    $onDark = $brand->logo('dark');
@endphp
@if ($main || $onDark)
    @if ($main)
        <img src="{{ $main->url() }}" alt="{{ $brand->logoAlt() }}" @class(['h-8 w-auto max-w-48 object-contain', 'dark:hidden' => (bool) $onDark, 'dark:brightness-0 dark:invert' => ! $onDark])>
    @endif
    @if ($onDark)
        <img src="{{ $onDark->url() }}" alt="{{ $brand->logoAlt() }}" @class(['h-8 w-auto max-w-48 object-contain', 'hidden dark:block' => (bool) $main])>
    @endif
@else
    <span style="display:inline-flex;align-items:center;gap:.6rem">
        <img src="{{ asset('favicon.svg') }}" alt="" style="height:1.9rem;width:1.9rem">
        <span style="font-family:'Newsreader Variable',Georgia,serif;font-size:1.2rem;font-weight:600;letter-spacing:-.01em" class="text-gray-950 dark:text-white">{{ $brand->displayTitle() }}</span>
    </span>
@endif
