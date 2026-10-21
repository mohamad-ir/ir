@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="Live teaching" title="Webinars" :breadcrumbs="['Webinars' => null]"
        intro="Interactive online sessions with case discussion and live Q&A. Can’t make it live? Registered participants receive the recording." />

    <div class="container-site section">
        <h2 class="h-section !text-3xl">Upcoming</h2>
        @if ($upcoming->isEmpty())
            <x-empty-state icon="heroicon-o-video-camera" title="No webinars scheduled right now" text="Subscribe to the newsletter to hear about the next live session first." class="mt-6">
                <a href="{{ route('newsletter.show') }}" class="btn btn-primary mt-6">Notify me</a>
            </x-empty-state>
        @else
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                @foreach ($upcoming as $webinar)
                    <x-webinar-card :webinar="$webinar" :registered="in_array($webinar->id, $registered, true)" />
                @endforeach
            </div>
        @endif

        @if ($past->isNotEmpty())
            <h2 class="h-section mt-16 !text-3xl">Past webinars</h2>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                @foreach ($past as $webinar)
                    <x-webinar-card :webinar="$webinar" :registered="in_array($webinar->id, $registered, true)" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
