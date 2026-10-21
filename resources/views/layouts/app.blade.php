@php
    $seo = app(\App\Services\Seo\SeoManager::class);
    $theme = app(\App\Services\Theme\ThemeService::class);
    $favicon = \App\Models\Media::find(setting('branding.favicon_id'));
    $headerStyle = setting('theme.header_style', 'light');
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <script>document.documentElement.classList.add('js')</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo->fullTitle() }}</title>
    @include('partials.seo')
    @if ($favicon)
        <link rel="icon" href="{{ $favicon->url() }}">
        <link rel="apple-touch-icon" href="{{ $favicon->url() }}">
    @else
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @endif
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
    @include('partials.search-modal')
    @include('partials.cookie-banner')

    @livewireScripts
    @include('partials.analytics')
    @stack('scripts')
</body>
</html>
