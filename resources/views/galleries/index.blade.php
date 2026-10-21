@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="Image galleries" title="Galleries" :breadcrumbs="['Galleries' => null]"
        intro="Curated collections of OCT, fundus and angiography images, organised by topic. Open any album to browse the images full screen." />

    <div class="container-site section">
        @if ($galleries->isEmpty())
            <x-empty-state icon="heroicon-o-photo" title="No galleries yet" text="New image collections will appear here." />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($galleries as $gallery)
                    <x-gallery-card :gallery="$gallery" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
