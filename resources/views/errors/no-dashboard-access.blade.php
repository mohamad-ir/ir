@extends('layouts.app')
@section('content')
    <section class="container-site section">
        <div class="mx-auto max-w-xl text-center">
            <p class="eyebrow">Dashboard access</p>
            @if ($user && ! $user->is_active)
                <h1 class="mt-3 font-heading text-4xl font-medium text-ink sm:text-5xl">Your account is not active</h1>
                <p class="lead mt-5">This account has been deactivated, so it cannot use the dashboard or the member area. If you think this is a mistake, please contact the site administrator.</p>
            @else
                <h1 class="mt-3 font-heading text-4xl font-medium text-ink sm:text-5xl">This account cannot open the dashboard</h1>
                <p class="lead mt-5">You are signed in{{ $user ? ' as '.$user->name : '' }}, but your role does not include access to the administration dashboard. You can keep using the website and your account as usual.</p>
                <p class="mt-4 text-sm text-muted">If you should have access, ask a site administrator to give your role the “Sign in to the dashboard” permission (System › Roles &amp; permissions), then sign in again.</p>
            @endif
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @if ($user && $user->is_active)
                    <a href="{{ route('account.dashboard') }}" class="btn btn-primary">Go to my account</a>
                @endif
                <a href="{{ url('/') }}" class="btn btn-secondary">Back to the website</a>
                @if ($user)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost">Sign out and use another account</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
