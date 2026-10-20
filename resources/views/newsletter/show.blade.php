@extends('layouts.app')
@section('content')
    <section class="container-site section">
        <div class="mx-auto max-w-2xl text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-accent text-accent-contrast"><x-heroicon-o-envelope-open class="size-7" /></span>
            <h1 class="h-display mt-6 !text-4xl sm:!text-5xl">{{ setting('newsletter.signup_heading') }}</h1>
            <p class="lead mt-5">{{ setting('newsletter.signup_text') }}</p>
        </div>
        <div class="card mx-auto mt-10 max-w-xl p-6 sm:p-8">
            <livewire:newsletter-form :interests="['weekly_case', 'articles']" source="newsletter-page" />
        </div>
        <div class="mx-auto mt-12 grid max-w-3xl gap-6 text-center sm:grid-cols-3">
            @foreach ([['heroicon-o-photo', 'One real case', 'Images and a short history, every week.'], ['heroicon-o-light-bulb', 'Answer on demand', 'Reveal the diagnosis and pearls when ready.'], ['heroicon-o-shield-check', 'No spam', 'Unsubscribe in one click, any time.']] as [$icon, $t, $d])
                <div><x-dynamic-component :component="$icon" class="mx-auto size-7 text-accent" /><p class="mt-3 font-semibold text-ink">{{ $t }}</p><p class="mt-1 text-sm text-muted">{{ $d }}</p></div>
            @endforeach
        </div>
    </section>
@endsection
