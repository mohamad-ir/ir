@props(['items' => [], 'light' => false])
<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1.5 {{ $light ? 'text-white/60' : 'text-muted' }}" role="list">
        <li><a href="{{ url('/') }}" class="transition {{ $light ? 'hover:text-white' : 'hover:text-ink' }}">Home</a></li>
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-1.5">
                <x-heroicon-m-chevron-right class="size-3.5 opacity-50" />
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="transition {{ $light ? 'hover:text-white' : 'hover:text-ink' }}">{{ $label }}</a>
                @else
                    <span class="{{ $light ? 'text-white/85' : 'text-fg/80' }} line-clamp-1" aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
