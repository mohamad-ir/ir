@extends('auth.layout')
@section('form')
    <h1 class="font-heading text-4xl font-medium text-ink">Welcome back</h1>
    <p class="mt-2 text-muted">Sign in to see your saved cases and question-bank progress.</p>
    <form method="post" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <div class="flex items-center justify-between"><label for="password" class="field-label">Password</label><a href="{{ route('password.request') }}" class="mb-1.5 text-sm link">Forgot?</a></div>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="input">
        </div>
        <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="remember" class="checkbox"> Keep me signed in</label>
        <button class="btn btn-primary btn-lg w-full">Sign in</button>
    </form>
    @if (setting('site.registration_enabled', true))
        <p class="mt-8 text-center text-sm text-muted">New here? <a href="{{ route('register') }}" class="link">Create a free account</a></p>
    @endif
@endsection
