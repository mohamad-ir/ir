@php
    $items = collect([
        ['OCT case', 'cases', \App\Filament\Resources\Cases\CaseResource::class, 'heroicon-o-eye'],
        ['Article', 'articles', \App\Filament\Resources\Articles\ArticleResource::class, 'heroicon-o-document-text'],
        ['Video', 'videos', \App\Filament\Resources\Videos\VideoResource::class, 'heroicon-o-play-circle'],
        ['Exam question', 'questions', \App\Filament\Resources\Questions\QuestionResource::class, 'heroicon-o-clipboard-document-check'],
        ['Page', 'pages', \App\Filament\Resources\Pages\PageResource::class, 'heroicon-o-squares-plus'],
    ])->filter(fn ($i) => class_exists($i[2]) && $i[2]::canCreate());
@endphp
@if ($items->isNotEmpty())
    <x-filament::dropdown placement="bottom-end" teleport>
        <x-slot name="trigger">
            <x-filament::button icon="heroicon-m-plus" size="sm">New</x-filament::button>
        </x-slot>
        <x-filament::dropdown.list>
            @foreach ($items as [$label, $key, $resource, $icon])
                <x-filament::dropdown.list.item :href="$resource::getUrl('create')" tag="a" :icon="$icon">{{ $label }}</x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@endif
