@php
    $items = $data['items'] ?? collect();
    $layout = $data['layout'] ?? 'cards';
    $source = $data['source'] ?? 'cases';
    $defaultUrl = ['cases' => route('cases.index'), 'articles' => route('articles.index'), 'videos' => route('videos.index'), 'questions' => route('exam.practice')][$source];
    $count = $items->count();
@endphp
<x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null"
                   :action-label="$data['view_all_label'] ?? null" :action-url="($data['view_all_url'] ?? null) ?: $defaultUrl" />

@if ($items->isEmpty())
    <x-empty-state icon="heroicon-o-sparkles" title="New material is on its way" text="Content will appear here automatically as soon as it is published." />
@elseif ($layout === 'list')
    <div class="grid gap-x-10 gap-y-2 md:grid-cols-2">
        @foreach ($items as $item)
            @include('blocks.partials.feed-item', ['item' => $item, 'source' => $source, 'layout' => 'list'])
        @endforeach
    </div>
@elseif ($layout === 'feature' && $count > 1)
    <div class="grid gap-8 lg:grid-cols-12">
        <div class="lg:col-span-7">@include('blocks.partials.feed-item', ['item' => $items->first(), 'source' => $source, 'layout' => 'card'])</div>
        <div class="flex flex-col gap-2 lg:col-span-5">
            @foreach ($items->skip(1) as $item)
                @include('blocks.partials.feed-item', ['item' => $item, 'source' => $source, 'layout' => 'list'])
            @endforeach
        </div>
    </div>
@else
    <div class="grid gap-6 sm:grid-cols-2 {{ $count >= 4 && $count % 4 === 0 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
        @foreach ($items as $item)
            @include('blocks.partials.feed-item', ['item' => $item, 'source' => $source, 'layout' => 'card'])
        @endforeach
    </div>
@endif

@if (! empty($data['view_all_label']))
    <div class="mt-10 sm:hidden">
        <a href="{{ ($data['view_all_url'] ?? null) ?: $defaultUrl }}" class="btn btn-secondary w-full">{{ $data['view_all_label'] }}</a>
    </div>
@endif
