@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="OCT Case Library" title="Learn OCT one real case at a time"
                   intro="De-identified retina and uveitis cases with imaging, findings, differential diagnosis, discussion and clinical pearls — filter by disease, imaging modality and difficulty."
                   :breadcrumbs="['OCT cases' => null]">
        @if ($week = \App\Models\ClinicalCase::currentCaseOfWeek())
            <a href="{{ $week->urlPath() }}" class="mt-8 inline-flex items-center gap-3 rounded-full bg-surface py-1.5 pl-1.5 pr-4 text-sm shadow-[var(--shadow-card)] ring-1 ring-line transition hover:ring-line-strong">
                <span class="rounded-full bg-highlight px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-highlight-contrast">Case of the week</span>
                <span class="font-medium text-ink">{{ $week->title }}</span>
                <x-heroicon-m-arrow-right class="size-4 text-muted" />
            </a>
        @endif
    </x-page-header>
    <div class="container-site py-12 lg:py-16">
        <livewire:case-library />
    </div>
@endsection
