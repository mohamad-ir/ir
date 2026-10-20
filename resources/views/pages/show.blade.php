@extends('layouts.app')
@section('content')
    @if (! empty($livePreview))
        <script>window.addEventListener('message', (e) => { if (e.origin === location.origin && e.data && e.data.type === 'oa-scroll') window.scrollTo(0, e.data.y); });</script>
    @endif
    @if (($isPreview ?? false) && empty($livePreview))
        <div class="bg-highlight/15 text-center text-sm font-medium text-ink py-2">Preview — {{ $page->exists ? $page->displayStatus() : 'unsaved changes' }}</div>
    @endif
    @if ($page->show_title && ! ($isHome ?? false))
        <x-page-header :title="$page->title" :intro="$page->summary" :breadcrumbs="[$page->title => null]" />
    @endif
    {!! app(\App\Services\PageBuilder\BlockRegistry::class)->render($page->blocks, ['page' => $page, 'preview' => $isPreview ?? false]) !!}
    @if (empty($page->blocks) && ($isPreview ?? false))
        <div class="container-site section"><x-empty-state icon="heroicon-o-squares-plus" title="This page has no sections yet" text="Add sections with the page builder on the left." /></div>
    @endif
@endsection
