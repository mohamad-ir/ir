{{-- Live preview of the unsaved Site identity choices (Design › Theme & branding). --}}
@php
    $data = $getLivewire()->data ?? [];
    $b = $data['branding'] ?? [];
    $media = fn ($id) => filled($id) ? \App\Models\Media::find($id) : null;
    $light = $media($b['logo_id'] ?? null);
    $dark = $media($b['logo_dark_id'] ?? null);
    $icon = $media($b['favicon_id'] ?? null);
    $name = ($data['site']['name'] ?? null) ?: 'Site name';
    $title = ($b['logo_text'] ?? null) ?: $name;
    $short = ($b['short_name'] ?? null) ?: $name;
    $iconBg = preg_match('/^#[0-9a-f]{6}$/i', $b['icon_background'] ?? '') ? $b['icon_background'] : '#ffffff';
    $primary = $data['theme']['primary'] ?? '#12324f';
    $height = ['sm' => '28px', 'md' => '36px', 'lg' => '44px'][$b['logo_height'] ?? 'md'] ?? '36px';
    $iconUrl = $icon?->url() ?? asset('icons/icon-512.png');
    $fallback = fn ($onDark) => '<span style="display:inline-flex;align-items:center;gap:.55rem"><img src="'.asset('favicon.svg').'" alt="" style="height:28px;width:28px"><span style="font-family:Georgia,serif;font-size:1.1rem;font-weight:600;color:'.($onDark ? '#fff' : e($primary)).'">'.e($title).'</span></span>';
@endphp
<div class="space-y-4">
    <p class="text-sm font-medium text-gray-950 dark:text-white">Preview</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <figure>
            <div class="flex h-16 items-center rounded-lg px-4 ring-1 ring-gray-950/10" style="background:#fbfaf7">
                @if ($light)<img src="{{ $light->url() }}" alt="" style="height:{{ $height }};width:auto;max-width:100%;object-fit:contain">@else{!! $fallback(false) !!}@endif
            </div>
            <figcaption class="mt-1 text-xs text-gray-500">Light header</figcaption>
        </figure>
        <figure>
            <div class="flex h-16 items-center rounded-lg px-4" style="background:#0c151e">
                @if ($dark)<img src="{{ $dark->url() }}" alt="" style="height:{{ $height }};width:auto;max-width:100%;object-fit:contain">
                @elseif ($light)<img src="{{ $light->url() }}" alt="" style="height:{{ $height }};width:auto;max-width:100%;object-fit:contain">
                @else{!! $fallback(true) !!}@endif
            </div>
            <figcaption class="mt-1 text-xs {{ $light && ! $dark ? 'text-warning-600' : 'text-gray-500' }}">
                Dark mode, dark header and sign-in page{{ $light && ! $dark ? ' — add a logo for dark backgrounds if this is hard to read' : '' }}
            </figcaption>
        </figure>
    </div>
    <div class="flex flex-wrap items-end gap-x-6 gap-y-4">
        <figure class="min-w-0 flex-1 basis-56">
            <div class="rounded-t-lg bg-gray-200 px-2 pt-2 dark:bg-gray-800">
                <div class="flex w-56 max-w-full items-center gap-2 rounded-t-md bg-white px-3 py-2 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <img src="{{ $iconUrl }}" alt="" class="size-4 shrink-0 rounded-sm">
                    <span class="truncate">{{ $name }}</span>
                </div>
            </div>
            <figcaption class="mt-1 text-xs text-gray-500">Browser tab</figcaption>
        </figure>
        <figure class="text-center">
            <span class="mx-auto grid size-16 place-items-center overflow-hidden rounded-2xl shadow ring-1 ring-black/5" style="background:{{ $iconBg }}"><img src="{{ $iconUrl }}" alt="" class="size-full object-contain"></span>
            <figcaption class="mt-1 w-20 truncate text-xs text-gray-700 dark:text-gray-300">{{ $short }}</figcaption>
        </figure>
        <figure class="text-center">
            <span class="mx-auto grid size-16 place-items-center overflow-hidden rounded-full shadow ring-1 ring-black/5" style="background:{{ $iconBg }}"><img src="{{ $iconUrl }}" alt="" class="size-[80%] object-contain"></span>
            <figcaption class="mt-1 text-xs text-gray-500">Android</figcaption>
        </figure>
    </div>
</div>
