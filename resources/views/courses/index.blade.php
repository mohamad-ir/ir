@extends('layouts.app')
@section('content')
<x-banner-slider placement="courses" />
    <x-page-header eyebrow="Courses" title="Structured online courses" :breadcrumbs="['Courses' => null]"
        intro="Self-paced programmes in OCT interpretation, medical retina, uveitis and exam preparation — video lessons, real cases and clear take-home messages.">
        @unless ($isMember)
            <p class="mt-6 text-sm text-muted">Many courses are included with <a href="{{ route('membership') }}" class="link">membership</a>.</p>
        @endunless
    </x-page-header>

    <div class="container-site section">
        @if ($courses->isEmpty())
            <x-empty-state icon="heroicon-o-academic-cap" title="New courses are in preparation" text="Register your interest and you will be the first to know when enrolment opens.">
                <a href="{{ route('newsletter.show') }}" class="btn btn-primary mt-6">Keep me informed</a>
            </x-empty-state>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-course-card :course="$course" :enrolled="in_array($course->id, $enrolled, true)" :is-member="$isMember" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
