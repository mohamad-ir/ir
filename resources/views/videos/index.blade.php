@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="Video library" title="Watch, pause, learn"
                   intro="OCT interpretation, retina, uveitis, surgical cases, clinical examination and case discussions — with chapters and searchable transcripts."
                   :breadcrumbs="['Videos' => null]" />
    <div class="container-site py-12 lg:py-16">
        <livewire:video-library />
    </div>
@endsection
