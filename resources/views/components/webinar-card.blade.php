@props(['webinar', 'registered' => false])
@php $tz = config('app.display_timezone', config('app.timezone')); @endphp
<article {{ $attributes->merge(['class' => 'group card-interactive relative flex flex-col overflow-hidden sm:flex-row']) }}>
    <div class="flex shrink-0 flex-row items-center gap-4 bg-ink px-5 py-4 text-ink-contrast sm:w-36 sm:flex-col sm:justify-center sm:gap-1 sm:py-6 sm:text-center">
        <span class="text-xs font-semibold uppercase tracking-[.16em] text-white/60">{{ $webinar->starts_at->copy()->timezone($tz)->format('M') }}</span>
        <span class="font-heading text-4xl font-semibold leading-none">{{ $webinar->starts_at->copy()->timezone($tz)->format('j') }}</span>
        <span class="text-sm text-white/70">{{ $webinar->starts_at->copy()->timezone($tz)->format('D · H:i') }}</span>
    </div>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex flex-wrap items-center gap-2 text-sm">
            @if ($webinar->isLive())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-2.5 py-0.5 text-[0.7rem] font-bold uppercase tracking-wider text-white"><span class="size-1.5 animate-pulse rounded-full bg-white"></span> Live now</span>
            @elseif ($webinar->hasEnded())
                <span class="chip">{{ $webinar->recording_url ? 'Recording available' : 'Past webinar' }}</span>
            @endif
            @if ($registered)<span class="chip chip-accent">Registered</span>@endif
            <span class="text-muted">{{ $webinar->duration_minutes }} min · {{ $webinar->platform?->getLabel() }}</span>
        </div>
        <h3 class="mt-2 font-heading text-xl font-semibold leading-snug text-ink">
            <a href="{{ $webinar->urlPath() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $webinar->title }}</a>
        </h3>
        @if ($webinar->summary)<p class="mt-2 line-clamp-2 text-[0.9375rem] text-muted">{{ $webinar->summary }}</p>@endif
        <div class="mt-auto flex items-center justify-between pt-4">
            <span class="font-semibold text-ink">{{ $webinar->priceLabel() }}@if(! $webinar->isFree() && $webinar->free_for_members)<span class="ml-2 text-sm font-normal text-muted">· free for members</span>@endif</span>
            <span class="link-arrow">{{ $webinar->hasEnded() ? 'Details' : 'Register' }} <x-heroicon-m-arrow-right /></span>
        </div>
    </div>
</article>
