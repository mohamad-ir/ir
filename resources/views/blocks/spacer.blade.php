@php($size = ['sm' => 'h-4', 'md' => 'h-10', 'lg' => 'h-20'][$data['size'] ?? 'md'] ?? 'h-10')
<div class="{{ $size }} flex items-center justify-center" aria-hidden="true">
    @if (($data['style'] ?? 'line') === 'line')
        <hr class="w-full border-line">
    @elseif (($data['style'] ?? '') === 'dots')
        <span class="flex gap-2 text-accent/60"><span class="size-1.5 rounded-full bg-current"></span><span class="size-1.5 rounded-full bg-current"></span><span class="size-1.5 rounded-full bg-current"></span></span>
    @endif
</div>
