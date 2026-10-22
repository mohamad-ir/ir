@php $size = ($small ?? false) ? 'size-8 text-xs' : 'size-10 text-sm'; @endphp
@if ($person?->avatarUrl())
    <img src="{{ $person->avatarUrl() }}" alt="" class="{{ $size }} shrink-0 rounded-full object-cover" loading="lazy">
@else
    <span class="{{ $size }} grid shrink-0 place-items-center rounded-full bg-accent/10 font-semibold text-accent-ink" aria-hidden="true">{{ $person?->initials() ?: '?' }}</span>
@endif
