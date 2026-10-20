@extends('auth.layout')
@section('form')
    <h1 class="font-heading text-4xl font-medium text-ink">Reset your password</h1>
    <p class="mt-2 text-muted">Enter your email address and we’ll send you a secure link.</p>
    <form method="post" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <button class="btn btn-primary btn-lg w-full">Email reset link</button>
    </form>
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="link">Back to sign in</a></p>
@endsection
