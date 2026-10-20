@php
    $media = $getRecord();
    $usages = $media ? app(\App\Services\Media\MediaUsage::class)->find($media) : [];
@endphp
@if ($media)
<div class="space-y-6">
    <div class="overflow-hidden rounded-xl bg-gray-950 ring-1 ring-gray-950/5">
        @if ($media->isImage())
            <img src="{{ $media->url('lg') }}" alt="{{ $media->altText() }}" class="mx-auto max-h-[34rem] w-auto">
        @elseif ($media->isVideo())
            <video src="{{ $media->url() }}" controls class="mx-auto max-h-[34rem] w-full"></video>
        @else
            <div class="flex h-64 items-center justify-center text-2xl font-bold uppercase text-white/60">{{ $media->extension }}</div>
        @endif
    </div>
    <div class="grid gap-4 rounded-xl bg-white p-5 text-sm ring-1 ring-gray-950/5 sm:grid-cols-2 dark:bg-gray-900 dark:ring-white/10">
        <div><p class="text-xs uppercase tracking-wider text-gray-500">File</p><p class="mt-1 font-medium break-all">{{ $media->original_name }}</p></div>
        <div><p class="text-xs uppercase tracking-wider text-gray-500">Type & size</p><p class="mt-1 font-medium">{{ $media->mime_type }} · {{ $media->humanSize() }} @if($media->dimensions()) · {{ $media->dimensions() }} px @endif</p></div>
        <div><p class="text-xs uppercase tracking-wider text-gray-500">Uploaded</p><p class="mt-1 font-medium">{{ $media->created_at?->format('j M Y H:i') }} @if($media->uploader) by {{ $media->uploader->name }} @endif</p></div>
        <div><p class="text-xs uppercase tracking-wider text-gray-500">Optimised versions</p><p class="mt-1 font-medium">{{ $media->variants ? implode(', ', array_keys($media->variants)).' (WebP)' : '—' }}</p></div>
        <div class="sm:col-span-2" x-data="{ copied: false }" data-url="{{ $media->url() }}">
            <p class="text-xs uppercase tracking-wider text-gray-500">Public URL</p>
            <div class="mt-1 flex gap-2">
                <input readonly value="{{ $media->url() }}" class="w-full rounded-lg border-gray-200 bg-gray-50 text-xs dark:border-white/10 dark:bg-white/5">
                <x-filament::button size="sm" color="gray" x-on:click="navigator.clipboard.writeText($root.dataset.url); copied = true; setTimeout(() => copied = false, 1500)"><span x-text="copied ? 'Copied' : 'Copy'"></span></x-filament::button>
            </div>
        </div>
    </div>
    <div class="rounded-xl bg-white p-5 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm font-semibold">Where this file is used</p>
        @forelse ($usages as $u)
            <div class="mt-2 flex items-center justify-between text-sm"><span><span class="text-gray-500">{{ $u['type'] }}</span> · {{ $u['title'] }}</span>@if($u['url'])<a href="{{ $u['url'] }}" target="_blank" class="text-primary-600 hover:underline">View</a>@endif</div>
        @empty
            <p class="mt-2 text-sm text-gray-500">Not used anywhere yet.</p>
        @endforelse
    </div>
</div>
@endif
