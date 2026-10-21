@extends('layouts.app')
@section('content')
    <section class="container-site section">
        <div class="mx-auto max-w-xl text-center">
            <p class="eyebrow">Error 419</p>
            <h1 class="mt-3 font-heading text-5xl font-medium text-ink">Page expired</h1>
            <p class="lead mt-5">Your session has expired for security reasons. Please go back, refresh the page and try again.</p>
            <div class="mt-8 flex justify-center gap-3">
                <a href="{{ url('/') }}" class="btn btn-secondary">Home</a>
                <a href="{{ route('cases.index') }}" class="btn btn-ghost">OCT case library</a>
            </div>
        </div>
    </section>
@endsection
