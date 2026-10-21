@extends('layouts.app')
@section('content')
    <div class="grid min-h-[calc(100vh-4.25rem)] lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-ink lg:block">
            <div class="absolute inset-0 opacity-50" style="background: radial-gradient(70% 60% at 80% 20%, color-mix(in oklab, var(--color-accent) 50%, transparent), transparent 60%), radial-gradient(60% 50% at 10% 90%, color-mix(in oklab, var(--color-highlight) 30%, transparent), transparent 60%)"></div>
            <svg class="absolute inset-x-0 bottom-24 w-full text-white/10" viewBox="0 0 600 160" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 50c120 0 160 2 220 40 40 25 60 40 80 40s40-15 80-40c60-38 100-40 220-40" fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="M0 85c130 0 170 5 225 32 35 17 55 25 75 25s40-8 75-25c55-27 95-32 225-32" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path d="M0 125h600" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            <div class="relative flex h-full flex-col justify-between p-12 text-white">
                <x-logo :light="true" size="lg" />
                <div>
                    <p class="font-heading text-3xl leading-snug">“The best way to learn OCT is one real case at a time — and to commit to a diagnosis before you look at the answer.”</p>
                    <p class="mt-5 text-white/60">{{ setting('doctor.name') }}, {{ setting('doctor.credentials') }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-center px-5 py-12 sm:px-10">
            <div class="w-full max-w-md">
                @if (session('status'))
                    <div class="mb-6 rounded-ui bg-accent/10 px-4 py-3 text-sm text-fg ring-1 ring-accent/20" role="status">{{ session('status') }}</div>
                @endif
                @yield('form')
            </div>
        </div>
    </div>
@endsection
