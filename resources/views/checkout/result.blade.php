@extends('layouts.app')
@section('content')
    <div class="container-site grid min-h-[60vh] place-items-center py-16">
        <div class="max-w-lg text-center">
            @if ($state === 'cancelled')
                <x-heroicon-o-x-circle class="mx-auto size-12 text-muted" />
                <h1 class="mt-5 font-heading text-3xl font-medium text-ink">Payment cancelled</h1>
                <p class="mt-3 text-muted">No payment was taken. You can return whenever you are ready.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3"><a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('courses.index') }}" class="btn btn-primary">Go back</a><a href="{{ route('contact') }}" class="btn btn-secondary">Questions? Contact us</a></div>
            @elseif ($state === 'pending')
                <x-heroicon-o-clock class="mx-auto size-12 text-accent" />
                <h1 class="mt-5 font-heading text-3xl font-medium text-ink">Your payment is being processed</h1>
                <p class="mt-3 text-muted">Some payment methods (such as bank debits) take a few days to confirm. Access is granted automatically as soon as the payment clears, and we will email you.</p>
                <a href="{{ route('account.dashboard') }}" class="btn btn-primary mt-8">Go to my learning</a>
            @else
                <x-heroicon-o-check-circle class="mx-auto size-12 text-accent" />
                <h1 class="mt-5 font-heading text-3xl font-medium text-ink">Thank you — we are confirming your payment</h1>
                <p class="mt-3 text-muted">This usually takes a few seconds. Your purchase will appear in your account shortly, and a confirmation email is on its way.</p>
                <a href="{{ route('account.dashboard') }}" class="btn btn-primary mt-8">Go to my learning</a>
            @endif
        </div>
    </div>
@endsection
