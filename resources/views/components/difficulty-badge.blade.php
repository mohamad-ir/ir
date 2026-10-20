@props(['level'])
@php
    $value = $level instanceof \BackedEnum ? $level->value : $level;
    $label = $level instanceof \App\Enums\Difficulty ? $level->getLabel() : ucfirst((string) $value);
    $bars = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3][$value] ?? 2;
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs font-medium text-muted']) }} title="Difficulty: {{ $label }}">
    <span class="flex items-end gap-[2px]" aria-hidden="true">
        @for ($i = 1; $i <= 3; $i++)
            <span class="w-[3px] rounded-full {{ $i <= $bars ? 'bg-accent' : 'bg-line-strong' }}" style="height: {{ 4 + $i * 3 }}px"></span>
        @endfor
    </span>
    {{ $label }}
</span>
