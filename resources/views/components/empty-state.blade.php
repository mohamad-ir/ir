@props(['icon' => 'heroicon-o-magnifying-glass', 'title' => 'Nothing here yet', 'text' => null])
<div {{ $attributes->merge(['class' => 'rounded-card border border-dashed border-line-strong px-6 py-16 text-center']) }}>
    <x-dynamic-component :component="$icon" class="mx-auto size-10 text-muted/60" />
    <p class="mt-4 font-heading text-xl font-semibold text-ink">{{ $title }}</p>
    @if ($text)<p class="mx-auto mt-2 max-w-md text-muted">{{ $text }}</p>@endif
    {{ $slot }}
</div>
