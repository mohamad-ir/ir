@php
    $spacing = $type === 'spacer' ? 'none' : ($data['_spacing'] ?? 'normal');
    $width = $data['_width'] ?? 'standard';
    $visibility = $data['_visibility'] ?? 'all';
    $classes = collect([
        'tone-'.(in_array($tone, ['default', 'surface', 'muted', 'accent', 'ink']) ? $tone : 'default'),
        'relative',
        match ($spacing) { 'none' => 'py-0', 'tight' => 'py-8 lg:py-12', 'loose' => 'py-20 lg:py-32', default => 'section' },
        $visibility === 'desktop' ? 'hide-mobile' : null,
        $visibility === 'mobile' ? 'hide-desktop' : null,
    ])->filter()->implode(' ');
    $containerStyle = match ($width) { 'narrow' => 'max-width: 52rem', 'wide' => 'max-width: 88rem', default => '' };
@endphp
<section @if (! empty($data['_anchor'])) id="{{ $data['_anchor'] }}" @endif class="{{ $classes }}" data-block="{{ $type }}">
    <div class="container-site" @if ($containerStyle) style="{{ $containerStyle }}" @endif>
        @include('blocks.'.$type, ['data' => $data, 'first' => $first, 'tone' => $tone])
    </div>
</section>
