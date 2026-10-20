@extends('layouts.app')
@section('content')
    <div class="container-site section text-center">
        <x-logo size="lg" class="justify-center" />
        <h1 class="h-display mx-auto mt-10 max-w-3xl">{{ setting('site.tagline') }}</h1>
        <p class="lead mx-auto mt-5 max-w-2xl">{{ setting('site.description') }}</p>
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <a href="{{ route('cases.index') }}" class="btn btn-primary btn-lg">Browse OCT cases</a>
            <a href="{{ route('videos.index') }}" class="btn btn-secondary btn-lg">Watch videos</a>
        </div>
        @auth @if (auth()->user()->isStaff())
            <p class="mt-12 text-sm text-muted">No homepage is selected yet. Choose one in the dashboard under Design › Pages.</p>
        @endif @endauth
    </div>
@endsection
