<div class="mx-auto {{ ($data['align'] ?? 'left') === 'center' ? 'text-center' : '' }}" style="max-width: var(--container-prose)">
    @if (! empty($data['heading']))
        <h2 class="h-section mb-6">{{ $data['heading'] }}</h2>
    @endif
    <div class="prose prose-lg prose-clinical {{ $tone === 'ink' ? 'prose-invert' : '' }}">{!! $data['html'] !!}</div>
</div>
