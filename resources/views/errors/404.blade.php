@extends('layouts.app')
@section('content')
    <section class="container-site section">
        <div class="mx-auto max-w-xl text-center">
            <p class="eyebrow">Error 404</p>
            <h1 class="mt-3 font-heading text-5xl font-medium text-ink">Page not found</h1>
            <p class="lead mt-5">The page you are looking for has moved or no longer exists. Try searching the case library instead.</p>
            @if (true)
            <form action="{{ route('search') }}" method="get" class="mx-auto mt-8 flex max-w-md gap-2" role="search">
                <label for="q404" class="sr-only">Search</label>
                <input id="q404" type="search" name="q" class="input" placeholder="Search cases, articles, videos…">
                <button class="btn btn-primary">Search</button>
            </form>
            @endif
            <div class="mt-8 flex justify-center gap-3">
                <a href="{{ url('/') }}" class="btn btn-secondary">Home</a>
                <a href="{{ route('cases.index') }}" class="btn btn-ghost">OCT case library</a>
            </div>
        </div>
    </section>
@endsection
