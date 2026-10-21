@php $flash = session('status') ? ['ok', session('status')] : (session('error') ? ['error', session('error')] : null); @endphp
@if ($flash)
    <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 9000)" x-show="open" x-transition.opacity.duration.300ms
         class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-lg items-start gap-3 rounded-card bg-ink px-5 py-4 text-sm text-ink-contrast shadow-[var(--shadow-pop)] sm:bottom-6"
         role="{{ $flash[0] === 'error' ? 'alert' : 'status' }}">
        @if ($flash[0] === 'error')
            <x-heroicon-s-exclamation-triangle class="mt-0.5 size-5 shrink-0 text-highlight" />
        @else
            <x-heroicon-s-check-circle class="mt-0.5 size-5 shrink-0 text-accent" />
        @endif
        <p class="flex-1 leading-relaxed">{{ $flash[1] }}</p>
        <button type="button" x-on:click="open = false" class="-m-1 rounded p-1 text-white/60 hover:text-white" aria-label="Dismiss"><x-heroicon-m-x-mark class="size-4" /></button>
    </div>
@endif
