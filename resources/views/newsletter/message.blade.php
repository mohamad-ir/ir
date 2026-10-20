@extends('layouts.app')
@section('content')
    <section class="container-site section">
        <div class="mx-auto max-w-lg text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-accent/10 text-accent-ink"><x-dynamic-component :component="$icon" class="size-8" /></span>
            <h1 class="mt-6 font-heading text-4xl font-medium text-ink">{{ $title }}</h1>
            <p class="lead mt-4">{{ $text }}</p>
            <a href="{{ $cta['url'] }}" class="btn btn-primary btn-lg mt-8">{{ $cta['label'] }}</a>
        </div>
    </section>
@endsection
