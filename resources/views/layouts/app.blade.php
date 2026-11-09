@php
    $seo = app(\App\Services\Seo\SeoManager::class);
    $theme = app(\App\Services\Theme\ThemeService::class);
    $headerStyle = setting('theme.header_style', 'light');
    $colorMode = \App\Support\ColorMode::current();
    $darkPaper = $theme->darkVars(\App\Support\Settings::group('theme'))['--color-paper'];
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth{{ $colorMode === 'dark' ? ' dark' : '' }}" data-color-mode="{{ $colorMode }}">
<head>
    <meta charset="utf-8">
    {{-- Runs before the first paint so the page never flashes in the wrong colours. --}}
    <script>(function(){var d=document.documentElement,q=window.matchMedia('(prefers-color-scheme: dark)');d.classList.add('js');function a(){var m=d.getAttribute('data-color-mode'),k=m==='dark'||(m==='system'&&q.matches);d.classList.toggle('dark',k);var t=document.querySelector('meta[name=theme-color]');if(t)t.content=k?t.dataset.dark:t.dataset.light}window.applyColorMode=a;a();q.addEventListener&&q.addEventListener('change',a);document.addEventListener('DOMContentLoaded',a)})()</script>
    <meta name="theme-color" content="{{ setting('theme.background') }}" data-light="{{ setting('theme.background') }}" data-dark="{{ $darkPaper }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo->fullTitle() }}</title>
    @include('partials.seo')
    @unless (is_array(setting('branding.icon_set')))
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @endunless
    {{ app(\App\Services\Branding\BrandingService::class)->headTags() }}
    <link rel="alternate" type="application/rss+xml" title="{{ setting('site.name') }}" href="{{ route('feed') }}">
    <style>{!! $theme->css() !!}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-screen flex flex-col {{ $bodyClass ?? '' }}" x-data x-on:keydown.window.prevent.slash="$dispatch('open-search')" x-on:keydown.window.meta.k.prevent="$dispatch('open-search')" x-on:keydown.window.ctrl.k.prevent="$dispatch('open-search')">
    <a href="#main" class="skip-link">Skip to content</a>

    @include('partials.announcement')
    @include('partials.header', ['headerStyle' => $headerStyle])

    <main id="main" class="flex-1">
        @yield('content')
    </main>
    @unless (request()->routeIs('account.*'))
        @include('partials.flash')
    @endunless

    @include('partials.footer')
    @include('partials.back-to-top')
    @include('partials.search-modal')
    @include('partials.cookie-banner')

    @livewireScripts
    @include('partials.analytics')
    @stack('scripts')
</body>
</html>
