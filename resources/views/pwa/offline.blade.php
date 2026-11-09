@php
    $theme = app(\App\Services\Theme\ThemeService::class);
    $brand = app(\App\Services\Branding\BrandingService::class);
@endphp
<!DOCTYPE html>
{{-- Offline page: stored when the app is installed and shown when a page is not available offline.
     Rendered without any account information so it is safe to keep on the device. --}}
<html lang="en" data-color-mode="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Offline — {{ $brand->name() }}</title>
    <script>(function(){var d=document.documentElement,m=(document.cookie.match(/(?:^|; )color_mode=(light|dark|system)/)||[])[1]||'system';d.setAttribute('data-color-mode',m);if(m==='dark'||(m==='system'&&matchMedia('(prefers-color-scheme: dark)').matches))d.classList.add('dark')})()</script>
    <style>{!! $theme->css() !!}</style>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-paper text-fg">
    <main class="container-site flex min-h-screen flex-col items-center justify-center py-16 text-center">
        <x-logo size="lg" />
        <div class="mt-10 grid size-16 place-items-center rounded-full bg-accent/10 text-accent-ink">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M8.5 16.4a5 5 0 017.1 0M5.6 13.3a9 9 0 0110-2.3m3 1.2a9 9 0 011 1.1M2.7 10.2A13 13 0 018 7.1m5.2-.4a13 13 0 018.1 3.5M12 20h.01"/></svg>
        </div>
        <h1 class="mt-6 font-heading text-3xl font-medium text-ink sm:text-4xl">You are offline</h1>
        <p class="lead mx-auto mt-4 max-w-md">This page is not available without a connection. It will load as soon as you are back online.</p>
        <button type="button" onclick="location.reload()" class="btn btn-primary mt-8">Try again</button>

        <section id="saved" class="mt-14 w-full max-w-lg text-left" hidden aria-labelledby="saved-heading">
            <h2 id="saved-heading" class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Available offline</h2>
            <ul id="saved-list" class="card mt-3 divide-y divide-line" role="list"></ul>
        </section>
    </main>
    <script>
        // Pages read recently as a guest are kept on this device for offline reading.
        fetch('/__pwa/pages.json').then(function (r) { return r.ok ? r.json() : { pages: [] }; }).then(function (index) {
            var list = document.getElementById('saved-list');
            (index.pages || []).slice(0, 12).forEach(function (p) {
                var li = document.createElement('li'), a = document.createElement('a');
                a.href = p.url; a.className = 'block px-4 py-3 text-sm font-medium text-ink hover:bg-subtle';
                a.textContent = p.title.replace(/\s+\|\s+.*$/, '');
                li.appendChild(a); list.appendChild(li);
            });
            if (list.children.length) document.getElementById('saved').hidden = false;
        }).catch(function () {});
        window.addEventListener('online', function () { location.reload(); });
    </script>
</body>
</html>
