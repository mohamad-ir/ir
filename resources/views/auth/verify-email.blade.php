@extends('auth.layout')
@section('form')
    <span class="grid size-14 place-items-center rounded-full bg-accent/10 text-accent-ink"><x-heroicon-o-envelope class="size-7" /></span>
    <h1 class="mt-6 font-heading text-4xl font-medium text-ink">Check your inbox</h1>
    <p class="mt-3 text-muted">We sent a verification link to <strong class="text-fg">{{ auth()->user()->email }}</strong>. Click it to activate your account.</p>
    <form method="post" action="{{ route('verification.send') }}" class="mt-8">
        @csrf
        <button class="btn btn-secondary">Resend verification email</button>
    </form>
    <p class="mt-8 text-sm"><a href="{{ route('cases.index') }}" class="link">Continue to the case library</a></p>
@endsection
