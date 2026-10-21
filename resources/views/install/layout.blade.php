<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Install — {{ $title ?? 'Setup' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-subtle">
    <div class="mx-auto max-w-2xl px-5 py-12">
        <div class="mb-8 flex items-center gap-3">
            <img src="{{ asset('favicon.svg') }}" alt="" class="size-10">
            <div>
                <p class="font-heading text-xl font-semibold text-ink">Website installation</p>
                <p class="text-sm text-muted">Ophthalmology education platform · v{{ config('cms.version') }}</p>
            </div>
        </div>
        <ol class="mb-8 flex gap-2 text-xs font-semibold uppercase tracking-wider">
            @foreach (['Requirements', 'Database', 'Administrator', 'Done'] as $i => $step)
                <li class="flex-1 rounded-full px-3 py-1.5 text-center {{ ($active ?? 0) === $i ? 'bg-ink text-ink-contrast' : (($active ?? 0) > $i ? 'bg-accent/15 text-accent-ink' : 'bg-surface text-muted ring-1 ring-line') }}">{{ $i + 1 }}. {{ $step }}</li>
            @endforeach
        </ol>
        <div class="card p-6 sm:p-8">
            @yield('body')
        </div>
    </div>
</body>
</html>
