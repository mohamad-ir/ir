@props(['eyebrow' => null, 'heading' => null, 'intro' => null, 'align' => 'left', 'actionLabel' => null, 'actionUrl' => null, 'level' => 'h2'])
@if ($eyebrow || $heading || $intro)
    <div {{ $attributes->merge(['class' => 'mb-10 flex flex-col gap-6 '.($align === 'center' ? 'items-center text-center' : 'sm:flex-row sm:items-end sm:justify-between')]) }}>
        <div class="{{ $align === 'center' ? 'max-w-2xl' : 'max-w-3xl' }}">
            @if ($eyebrow)<p class="eyebrow mb-3">{{ $eyebrow }}</p>@endif
            @if ($heading)<{{ $level }} class="h-section text-balance">{!! \App\Support\Typography::highlight($heading) !!}</{{ $level }}>@endif
            @if ($intro)<p class="mt-4 text-lg leading-relaxed text-muted">{{ $intro }}</p>@endif
        </div>
        @if ($actionLabel && $actionUrl && $align !== 'center')
            <a href="{{ $actionUrl }}" class="link-arrow shrink-0">{{ $actionLabel }} <x-heroicon-m-arrow-right /></a>
        @endif
    </div>
@endif
