@extends('layouts.app')
@php $tz = config('app.display_timezone', config('app.timezone')); @endphp
@section('content')
<section class="relative overflow-hidden bg-ink text-ink-contrast">
    @if ($webinar->featuredMedia)
        <x-media-img :media="$webinar->featuredMedia" variant="lg" sizes="100vw" class="absolute inset-0 size-full object-cover opacity-20" :eager="true" alt="" />
        <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/40"></div>
    @else
        <div class="bg-retina-lines absolute inset-0 opacity-20"></div>
    @endif
    <div class="container-site relative py-12 sm:py-16">
        @if ($isPreview)
            <div class="mb-6 rounded-ui bg-highlight/20 px-4 py-2.5 text-sm font-medium">Preview — status: {{ $webinar->displayStatus() }}</div>
        @endif
        <x-breadcrumbs :items="['Webinars' => route('webinars.index'), $webinar->title => null]" :light="true" />
        <div class="mt-6 max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                @if ($webinar->isLive())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white"><span class="size-1.5 animate-pulse rounded-full bg-white"></span> Live now</span>
                @elseif ($webinar->hasEnded())
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider">Past webinar</span>
                @else
                    <span class="eyebrow !text-white/70">Live webinar</span>
                @endif
            </div>
            <h1 class="mt-4 font-heading text-4xl font-medium leading-[1.1] text-white sm:text-5xl">{{ $webinar->title }}</h1>
            @if ($webinar->summary)<p class="mt-5 text-xl leading-relaxed text-white/80">{{ $webinar->summary }}</p>@endif
            <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-2 text-white/80">
                <span class="inline-flex items-center gap-2"><x-heroicon-m-calendar-days class="size-5 text-white/60" />
                    <time datetime="{{ $webinar->starts_at->toIso8601String() }}" x-data x-init="$el.title = 'Your time: ' + new Date($el.getAttribute('datetime')).toLocaleString([], { dateStyle: 'full', timeStyle: 'short' })">{{ $webinar->displayStart() }}</time>
                </span>
                <span class="inline-flex items-center gap-2"><x-heroicon-m-clock class="size-5 text-white/60" /> {{ $webinar->duration_minutes }} minutes</span>
                <span class="inline-flex items-center gap-2"><x-heroicon-m-video-camera class="size-5 text-white/60" /> {{ $webinar->platform?->getLabel() }}</span>
            </div>
            <p class="mt-2 text-sm text-white/55" x-data="{ local: '' }" x-init="local = new Date(@js($webinar->starts_at->toIso8601String())).toLocaleString([], { weekday: 'long', hour: '2-digit', minute: '2-digit', timeZoneName: 'short' })" x-show="local" x-cloak>In your time zone: <span x-text="local"></span></p>
        </div>
    </div>
</section>

<div class="container-site grid gap-12 py-12 lg:grid-cols-[minmax(0,1fr)_23rem] lg:py-16">
    <div class="min-w-0 space-y-12">
        @if ($webinar->hasEnded() && $webinar->recordingEmbedUrl())
            <section>
                <h2 class="h-section !text-3xl">Recording</h2>
                <div class="mt-6">
                    @if ($canWatch)
                        <div class="viewer-frame aspect-video"><iframe src="{{ $webinar->recordingEmbedUrl() }}" title="Recording: {{ $webinar->title }}" class="size-full" allow="fullscreen; picture-in-picture; encrypted-media" allowfullscreen></iframe></div>
                    @else
                        <div class="rounded-card bg-ink p-8 text-center text-ink-contrast">
                            <x-heroicon-o-lock-closed class="mx-auto size-8 text-white/70" />
                            <p class="mt-3 font-heading text-2xl font-semibold">The recording is available to participants{{ $webinar->free_for_members ? ' and members' : '' }}</p>
                            <div class="mt-5 flex flex-wrap justify-center gap-3">
                                @guest<a href="{{ route('login', ['redirect' => $webinar->urlPath()]) }}" class="btn btn-light">Sign in</a>@endguest
                                @if ($webinar->free_for_members && ! $isMember)<a href="{{ route('membership') }}" class="btn btn-outline-light">Membership options</a>@endif
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if (filled($webinar->description))
            <section>
                <h2 class="h-section !text-3xl">About this session</h2>
                <div class="prose prose-lg prose-clinical mt-6">{!! \App\Support\RichText::render($webinar->description) !!}</div>
            </section>
        @endif

        @if ($webinar->author)
            <section class="flex flex-col gap-5 rounded-card bg-surface p-6 ring-1 ring-line sm:flex-row sm:items-center">
                @if ($webinar->author->avatarUrl())
                    <img src="{{ $webinar->author->avatarUrl() }}" alt="" class="size-20 shrink-0 rounded-full object-cover">
                @else
                    <span class="grid size-20 shrink-0 place-items-center rounded-full bg-accent font-heading text-2xl font-bold text-accent-contrast">{{ $webinar->author->initials() }}</span>
                @endif
                <div>
                    <p class="eyebrow">Presented by</p>
                    <p class="mt-1 font-heading text-xl font-semibold text-ink">{{ $webinar->author->displayName() }}</p>
                    @if ($webinar->author->bio)<p class="mt-2 text-[0.95rem] text-muted">{{ $webinar->author->bio }}</p>@endif
                </div>
            </section>
        @endif
    </div>

    <aside class="-order-1 lg:order-none">
        <div class="card p-6 lg:sticky lg:top-28">
            @if ($registration)
                <div class="flex items-center gap-2 font-semibold text-accent-ink"><x-heroicon-s-check-circle class="size-6" /> You are registered</div>
                @unless ($webinar->hasEnded())
                    @if ($webinar->joinWindowOpen())
                        <a href="{{ route('webinars.join', $webinar->slug) }}" class="btn btn-primary btn-lg mt-5 w-full" target="_blank" rel="noopener">Join now <x-heroicon-m-arrow-top-right-on-square /></a>
                    @else
                        <div class="mt-5" x-data="countdown(@js($webinar->starts_at->toIso8601String()))">
                            <p class="text-sm text-muted">Starts in</p>
                            <p class="mt-1 font-heading text-3xl font-semibold tabular-nums text-ink" x-text="label">{{ $webinar->starts_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</p>
                            <p class="mt-3 text-sm text-muted">The join button appears here {{ \App\Models\Webinar::JOIN_OPENS_MINUTES }} minutes before the start. The link is also in your confirmation email.</p>
                        </div>
                    @endif
                    <a href="{{ route('webinars.calendar', $webinar->slug) }}" class="btn btn-secondary mt-3 w-full"><x-heroicon-m-calendar /> Add to calendar</a>
                    @if ($registration->source !== 'purchase')
                        <form method="post" action="{{ route('webinars.cancel', $webinar->slug) }}" class="mt-4 text-center" x-data x-on:submit="if (!confirm('Cancel your registration?')) $event.preventDefault()">@csrf
                            <button class="text-sm text-muted underline-offset-4 hover:underline">Cancel my registration</button>
                        </form>
                    @endif
                @endunless
            @elseif ($webinar->hasEnded())
                <p class="font-semibold text-ink">This webinar has taken place.</p>
                <p class="mt-2 text-sm text-muted">{{ $webinar->recording_url ? 'The recording is available to participants'.($webinar->free_for_members ? ' and members.' : '.') : 'Browse upcoming sessions for the next opportunity.' }}</p>
                <a href="{{ route('webinars.index') }}" class="btn btn-secondary mt-5 w-full">Upcoming webinars</a>
            @else
                <p class="font-heading text-4xl font-semibold text-ink">{{ $freeForUser && ! $webinar->isFree() ? 'Free' : $webinar->priceLabel() }}</p>
                @if (! $webinar->isFree() && $freeForUser)
                    <p class="mt-1 text-sm text-accent-ink">Included with your membership</p>
                @elseif (! $webinar->isFree() && $webinar->free_for_members)
                    <p class="mt-1 text-sm text-muted">Free for members · <a href="{{ route('membership') }}" class="link">learn more</a></p>
                @endif
                @if (! $webinar->isFree() && ! $freeForUser)<x-currency-switcher class="mt-3" />@endif
                @if ($webinar->seatsLeft() !== null && $webinar->seatsLeft() <= 20 && ! $webinar->isFull())
                    <p class="mt-3 text-sm font-medium text-highlight">Only {{ $webinar->seatsLeft() }} {{ \Illuminate\Support\Str::plural('place', $webinar->seatsLeft()) }} left</p>
                @endif

                @if (! $webinar->acceptsRegistrations())
                    <p class="mt-5 rounded-ui bg-highlight/10 px-4 py-3 text-sm ring-1 ring-highlight/25">{{ $webinar->isFull() ? 'This webinar is fully booked.' : 'Registration is closed.' }}</p>
                @else
                    @guest
                        <a href="{{ route('register', ['redirect' => $webinar->urlPath()]) }}" class="btn btn-primary btn-lg mt-6 w-full">Create an account to register</a>
                        <p class="mt-3 text-center text-sm text-muted">Already registered? <a href="{{ route('login', ['redirect' => $webinar->urlPath()]) }}" class="link">Sign in</a></p>
                    @else
                        @if ($freeForUser)
                            <form method="post" action="{{ route('webinars.register', $webinar->slug) }}" class="mt-6">@csrf
                                <button class="btn btn-primary btn-lg w-full">Reserve my place <x-heroicon-m-arrow-right /></button>
                            </form>
                        @elseif ($paymentsEnabled)
                            <form method="post" action="{{ route('checkout.webinar', $webinar->slug) }}" class="mt-6 space-y-3" x-data="{ busy: false }" x-on:submit="busy = true">@csrf
                                @include('checkout.consent')
                                <button class="btn btn-primary btn-lg w-full" x-bind:disabled="busy">
                                    <span x-show="!busy">Buy ticket — {{ $webinar->priceLabel() }}</span><span x-show="busy" x-cloak>Redirecting to secure payment…</span>
                                </button>
                            </form>
                            <p class="mt-4 flex items-center justify-center gap-2 text-xs text-muted"><x-heroicon-m-lock-closed class="size-3.5" /> Secure payment by Stripe</p>
                        @else
                            <p class="mt-5 rounded-ui bg-highlight/10 px-4 py-3 text-sm ring-1 ring-highlight/25">Ticket sales open soon.</p>
                        @endif
                    @endguest
                @endif
            @endif

            <ul class="mt-6 space-y-2.5 border-t border-line pt-5 text-sm text-fg/85" role="list">
                <li class="flex gap-2.5"><x-heroicon-o-video-camera class="size-5 shrink-0 text-muted" /> Online via {{ $webinar->platform?->getLabel() }} — no travel</li>
                <li class="flex gap-2.5"><x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0 text-muted" /> Live Q&A with the presenter</li>
                <li class="flex gap-2.5"><x-heroicon-o-bell-alert class="size-5 shrink-0 text-muted" /> Email reminders the day before and one hour before</li>
                <li class="flex gap-2.5"><x-heroicon-o-film class="size-5 shrink-0 text-muted" /> Recording for participants afterwards</li>
            </ul>
        </div>
    </aside>
</div>
@endsection
