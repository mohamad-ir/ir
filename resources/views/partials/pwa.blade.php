{{-- Installable app (PWA): update notice, install suggestion on phones and iOS instructions. State: Alpine store "pwa" (resources/js/pwa.js). --}}
@if (app(\App\Services\Pwa\PwaService::class)->enabled())
<div x-data class="contents">
    {{-- New version available --}}
    <div x-cloak x-show="$store.pwa.updateReady" x-transition data-floating-bottom role="status" aria-live="polite"
         class="fixed inset-x-4 bottom-4 z-40 mx-auto flex max-w-md items-center gap-3 rounded-card bg-surface p-4 text-sm text-fg shadow-[var(--shadow-pop)] ring-1 ring-line sm:left-6 sm:right-auto sm:mx-0"
         style="margin-bottom: env(safe-area-inset-bottom, 0px)">
        <x-heroicon-o-arrow-path class="size-5 shrink-0 text-accent-ink" aria-hidden="true" />
        <p class="flex-1">A new version of {{ setting('site.name') }} is available.</p>
        <button type="button" x-on:click="$store.pwa.refresh()" class="btn btn-primary btn-sm">Refresh</button>
    </div>

    {{-- Install suggestion (phones) --}}
    <div x-cloak x-show="$store.pwa.suggest" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full opacity-0"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-y-full opacity-0"
         data-floating-bottom role="dialog" aria-labelledby="pwa-suggest-title" aria-describedby="pwa-suggest-text"
         class="fixed inset-x-3 bottom-3 z-40 rounded-card bg-surface p-4 text-fg shadow-[var(--shadow-pop)] ring-1 ring-line"
         style="margin-bottom: env(safe-area-inset-bottom, 0px)" x-on:keydown.escape="$store.pwa.dismiss()">
        <div class="flex items-start gap-3">
            <img src="{{ app(\App\Services\Branding\BrandingService::class)->iconUrl('icon-192') }}" alt="" class="size-12 shrink-0 rounded-xl ring-1 ring-line" width="48" height="48">
            <div class="min-w-0 flex-1">
                <p id="pwa-suggest-title" class="font-semibold text-ink">Get the {{ app(\App\Services\Branding\BrandingService::class)->shortName() }} app</p>
                <p id="pwa-suggest-text" class="mt-0.5 text-sm text-muted">Open cases faster from your home screen, full screen, and read recently viewed pages offline.</p>
            </div>
            <button type="button" x-on:click="$store.pwa.dismiss()" class="-mr-1 -mt-1 grid size-9 shrink-0 place-items-center rounded-full text-muted hover:bg-ink/5" aria-label="Close">
                <x-heroicon-m-x-mark class="size-5" />
            </button>
        </div>
        <div class="mt-3 flex justify-end gap-2">
            <button type="button" x-on:click="$store.pwa.dismiss()" class="btn btn-ghost btn-sm">Not now</button>
            <button type="button" x-on:click="$store.pwa.install('suggestion')" class="btn btn-primary btn-sm">
                <x-heroicon-m-arrow-down-tray /> <span x-text="$store.pwa.deferred ? 'Install' : 'How to install'">Install</span>
            </button>
        </div>
    </div>

    {{-- iPhone / iPad: there is no install button, so explain “Add to Home Screen” --}}
    <template x-teleport="body">
        <div x-cloak x-show="$store.pwa.iosHelp" class="fixed inset-0 z-[60] flex items-end justify-center p-3 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="pwa-ios-title"
             x-on:keydown.escape.window="$store.pwa.iosHelp = false">
            <div class="absolute inset-0 bg-black/50" x-on:click="$store.pwa.iosHelp = false"></div>
            <div class="card relative w-full max-w-sm p-5 shadow-[var(--shadow-pop)]" x-trap.noscroll="$store.pwa.iosHelp" style="margin-bottom: env(safe-area-inset-bottom, 0px)">
                <h2 id="pwa-ios-title" class="font-heading text-xl font-semibold text-ink">Add {{ app(\App\Services\Branding\BrandingService::class)->shortName() }} to your Home Screen</h2>
                <ol class="mt-4 space-y-3 text-sm">
                    <li class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent/10 font-semibold text-accent-ink">1</span>
                        <span>Tap the <strong>Share</strong> button
                            <svg viewBox="0 0 24 24" class="mx-0.5 inline size-5 align-text-bottom text-accent-ink" fill="none" stroke="currentColor" stroke-width="1.8" aria-label="Share icon" role="img"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0-12L8 7m4-4 4 4M6 11H5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8a1 1 0 0 0-1-1h-1"/></svg>
                            in the browser bar.</span></li>
                    <li class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent/10 font-semibold text-accent-ink">2</span>
                        <span>Scroll down and choose <strong>Add to Home Screen</strong>
                            <svg viewBox="0 0 24 24" class="mx-0.5 inline size-5 align-text-bottom text-accent-ink" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>.</span></li>
                    <li class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent/10 font-semibold text-accent-ink">3</span>
                        <span>Tap <strong>Add</strong>. The app opens full screen from your Home Screen.</span></li>
                </ol>
                <p class="mt-4 text-xs text-muted">On iPhone and iPad this works in Safari, and in Chrome or Edge on iOS 16.4 and later.</p>
                <div class="mt-5 flex justify-end"><button type="button" x-on:click="$store.pwa.iosHelp = false" class="btn btn-primary btn-sm">Got it</button></div>
            </div>
        </div>
    </template>
</div>
@endif
