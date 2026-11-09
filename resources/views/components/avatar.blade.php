@props(['user' => null, 'size' => 'md'])
{{--
    Profile photo with an initials fallback. Sizes: xs 32px, sm 40px, md 56px, lg 80px, xl 128px.
    data-avatar-user lets the account page swap the photo everywhere on the page after an upload.
--}}
@php
    $classes = ['xs' => 'size-8 text-xs', 'sm' => 'size-10 text-sm', 'md' => 'size-14 text-xl', 'lg' => 'size-20 text-2xl', 'xl' => 'size-32 text-4xl'][$size] ?? 'size-14 text-xl';
    $file = in_array($size, ['xs', 'sm'], true) ? 'sm' : ($size === 'xl' ? 'lg' : 'md');
    $url = $user?->avatarUrl($file);
    // Default avatar: a calm colour chosen from the account id, so each member keeps the same colour.
    $tones = ['bg-accent/15 text-accent-ink', 'bg-highlight/15 text-highlight-ink', 'bg-ink/10 text-ink', 'bg-emerald-500/15 text-emerald-700', 'bg-violet-500/15 text-violet-700 dark:text-violet-300', 'bg-sky-500/15 text-sky-700 dark:text-sky-300'];
    $tone = $user ? $tones[$user->id % count($tones)] : 'bg-subtle text-muted';
@endphp
<span {{ $attributes->merge(['class' => "relative inline-grid shrink-0 place-items-center overflow-hidden rounded-full font-heading font-semibold select-none {$classes} {$tone}"]) }}
      @if ($user) data-avatar-user="{{ $user->id }}" data-avatar-file="{{ $file }}" @endif aria-hidden="true">
    @if ($user)
        <span>{{ $user->initials() ?: '?' }}</span>
    @else
        <x-heroicon-s-user class="size-1/2" />
    @endif
    @if ($url)
        <img src="{{ $url }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy" decoding="async">
    @endif
</span>
