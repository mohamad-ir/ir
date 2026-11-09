{{-- Floating “Back to top” button for long public pages. Stays below menus and dialogs (z-30). --}}
<div x-data="backToTop" class="pointer-events-none fixed right-4 z-30 sm:right-6"
     :style="`bottom: calc(${lift ? lift + 12 : 16}px + env(safe-area-inset-bottom, 0px))`" style="bottom: 16px">
    <button type="button" x-cloak x-show="visible" x-on:click="top($event)"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-2 opacity-0"
            class="pointer-events-auto grid size-11 place-items-center rounded-full bg-surface text-ink shadow-[var(--shadow-lift)] ring-1 ring-line-strong transition hover:bg-subtle hover:ring-accent/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            aria-label="Back to top" title="Back to top">
        <x-heroicon-m-arrow-up class="size-5" aria-hidden="true" />
    </button>
</div>
