@extends('layouts.app')
@section('content')
    <section class="container-site section">
        <div class="card mx-auto max-w-lg p-8 text-center">
            <h1 class="font-heading text-3xl font-medium text-ink">Unsubscribe?</h1>
            <p class="mt-3 text-muted">{{ $subscriber->email }} will stop receiving emails from {{ setting('site.name') }}.</p>
            <form method="post" action="{{ route('newsletter.unsubscribe.confirm', $subscriber->token) }}" class="mt-8">
                @csrf
                <button class="btn btn-primary">Yes, unsubscribe me</button>
                <a href="{{ url('/') }}" class="btn btn-ghost">Cancel</a>
            </form>
        </div>
    </section>
@endsection
