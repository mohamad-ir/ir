{{-- Paywall / sign-up prompt for gated library content. --}}
@props(['content', 'dark' => true, 'thing' => 'material'])
@php
    $level = app(\App\Services\Access\ContentAccess::class)->level($content)->value;
    $members = $level === 'members';
@endphp
<div {{ $attributes->merge(['class' => 'text-center']) }}>
    <x-heroicon-o-lock-closed @class(['mx-auto size-8', 'text-white/70' => $dark, 'text-muted' => ! $dark]) />
    <p class="mt-3 font-heading text-2xl font-semibold">
        {{ $members ? 'This '.$thing.' is included with membership' : 'This '.$thing.' is free for registered members' }}
    </p>
    <p @class(['mx-auto mt-2 max-w-md text-sm', 'text-white/70' => $dark, 'text-muted' => ! $dark])>
        {{ $members ? 'Members get the full library, every course and live webinars.' : 'Create a free account in under a minute to continue.' }}
    </p>
    <div class="mt-5 flex flex-wrap justify-center gap-3">
        @if ($members)
            <a href="{{ route('membership') }}" class="btn {{ $dark ? 'btn-light' : 'btn-primary' }}">See membership options</a>
            @guest<a href="{{ route('login') }}" class="btn {{ $dark ? 'btn-outline-light' : 'btn-secondary' }}">Sign in</a>@endguest
        @else
            <a href="{{ route('register') }}" class="btn {{ $dark ? 'btn-light' : 'btn-primary' }}">Create free account</a>
            <a href="{{ route('login') }}" class="btn {{ $dark ? 'btn-outline-light' : 'btn-secondary' }}">Sign in</a>
        @endif
    </div>
</div>
