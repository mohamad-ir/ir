@extends('auth.layout')
@section('form')
    <h1 class="font-heading text-4xl font-medium text-ink">Choose a new password</h1>
    <form method="post" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required class="input">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="field-label">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="input">
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="field-label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required class="input">
        </div>
        <button class="btn btn-primary btn-lg w-full">Reset password</button>
    </form>
@endsection
