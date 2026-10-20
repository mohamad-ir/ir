@php($centered = ($data['style'] ?? 'split') === 'centered')
<div @class(['grid items-center gap-8', 'lg:grid-cols-2 lg:gap-16' => ! $centered, 'mx-auto max-w-2xl text-center' => $centered])>
    <div>
        <span class="mb-4 inline-grid size-12 place-items-center rounded-full bg-accent text-accent-contrast {{ $centered ? 'mx-auto' : '' }}"><x-heroicon-o-envelope-open class="size-6" /></span>
        <h2 class="h-section {{ $tone === 'ink' ? 'text-white' : '' }}">{{ $data['heading'] }}</h2>
        @if (! empty($data['text']))<p class="mt-4 text-lg text-muted">{{ $data['text'] }}</p>@endif
    </div>
    <div>
        <livewire:newsletter-form :interests="$data['interests'] ?? ['weekly_case']" :dark="$tone === 'ink'" source="page-block" :key="'nl-'.$index" />
    </div>
</div>
