@props(['eyebrow' => null, 'title', 'intro' => null, 'breadcrumbs' => []])
<section class="relative overflow-hidden border-b border-line bg-surface">
    <div class="bg-retina-lines absolute inset-0 opacity-70" aria-hidden="true"></div>
    <div class="container-site relative py-12 sm:py-16">
        @if ($breadcrumbs)
            <x-breadcrumbs :items="$breadcrumbs" class="mb-6" />
        @endif
        @if ($eyebrow)<p class="eyebrow mb-3">{{ $eyebrow }}</p>@endif
        <h1 class="h-display max-w-4xl">{{ $title }}</h1>
        @if ($intro)<p class="lead mt-5 max-w-2xl">{{ $intro }}</p>@endif
        {{ $slot }}
    </div>
</section>
