@extends('layouts.app')
@section('content')
    <div class="container-site grid min-h-[60vh] place-items-center py-14">
        <div class="w-full max-w-xl">
            <div class="card overflow-hidden">
                <div @class(['flex items-center gap-3 px-6 py-4 text-sm font-semibold', 'bg-emerald-50 text-emerald-800' => $certificate->isValid(), 'bg-red-50 text-red-800' => ! $certificate->isValid()])>
                    @if ($certificate->isValid())
                        <x-heroicon-s-check-badge class="size-6" /> Valid certificate issued by {{ setting('site.name') }}
                    @else
                        <x-heroicon-s-x-circle class="size-6" /> This certificate has been revoked
                    @endif
                </div>
                <dl class="divide-y divide-line px-6">
                    <div class="flex justify-between gap-6 py-4"><dt class="text-muted">Awarded to</dt><dd class="text-right font-heading text-xl font-semibold text-ink">{{ $certificate->recipient_name }}</dd></div>
                    <div class="flex justify-between gap-6 py-4"><dt class="text-muted">Course</dt><dd class="text-right font-medium text-ink">{{ $certificate->course_title }}</dd></div>
                    <div class="flex justify-between gap-6 py-4"><dt class="text-muted">Completed</dt><dd class="text-right text-ink">{{ $certificate->issued_at->format('j F Y') }}</dd></div>
                    @if ($certificate->hoursLabel())<div class="flex justify-between gap-6 py-4"><dt class="text-muted">Learning time</dt><dd class="text-right text-ink">{{ $certificate->hoursLabel() }}</dd></div>@endif
                    <div class="flex justify-between gap-6 py-4"><dt class="text-muted">Certificate ID</dt><dd class="text-right font-mono text-ink">{{ $certificate->code }}</dd></div>
                </dl>
                @if ($canDownload && $certificate->isValid())
                    <div class="border-t border-line px-6 py-4"><a href="{{ $certificate->downloadUrl() }}" class="btn btn-primary w-full"><x-heroicon-m-arrow-down-tray /> Download PDF</a></div>
                @endif
            </div>
            @if ($certificate->isValid() && $certificate->course?->isPublished())
                <p class="mt-6 text-center text-sm text-muted">About the course: <a href="{{ $certificate->course->urlPath() }}" class="link">{{ $certificate->course->title }}</a></p>
            @endif
        </div>
    </div>
@endsection
