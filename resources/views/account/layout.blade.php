@extends('layouts.app')
@section('content')
    <div class="border-b border-line bg-surface">
        <div class="container-site flex flex-col gap-6 py-8 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="grid size-14 place-items-center rounded-full bg-accent font-heading text-xl font-bold text-accent-contrast">{{ auth()->user()->initials() }}</span>
                <div>
                    <h1 class="font-heading text-3xl font-medium text-ink">{{ auth()->user()->displayName() }}</h1>
                    <p class="text-sm text-muted">{{ auth()->user()->career_stage?->getLabel() }} @if(auth()->user()->institution) · {{ auth()->user()->institution }} @endif</p>
                </div>
            </div>
            <nav class="flex flex-wrap gap-1 rounded-full bg-subtle p-1 ring-1 ring-line" aria-label="Account">
                <a href="{{ route('account.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-medium transition {{ request()->routeIs('account.dashboard') ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink' }}">My learning</a>
                <a href="{{ route('account.billing') }}" class="rounded-full px-4 py-2 text-sm font-medium transition {{ request()->routeIs('account.billing') ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink' }}">Billing</a>
                <a href="{{ route('account.profile') }}" class="rounded-full px-4 py-2 text-sm font-medium transition {{ request()->routeIs('account.profile') ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink' }}">Settings</a>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-full px-4 py-2 text-sm font-medium text-muted hover:text-ink">Sign out</button></form>
            </nav>
        </div>
    </div>
    <div class="container-site py-10 lg:py-12">
        @if (session('status'))
            <div class="mb-8 rounded-ui bg-accent/10 px-4 py-3 text-sm ring-1 ring-accent/20" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-8 rounded-ui bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert">{{ session('error') }}</div>
        @endif
        @if (! auth()->user()->hasVerifiedEmail())
            <div class="mb-8 flex flex-wrap items-center justify-between gap-3 rounded-ui bg-highlight/10 px-4 py-3 text-sm ring-1 ring-highlight/25">
                <span>Please verify your email address to secure your account.</span>
                <form method="post" action="{{ route('verification.send') }}">@csrf<button class="link">Resend link</button></form>
            </div>
        @endif
        @yield('account')
    </div>
@endsection
