@if (setting('site.cookie_banner', true) && filled(setting('analytics.ga4_measurement_id')))
    <div x-data="cookieConsent('{{ setting('analytics.ga4_measurement_id') }}')" x-cloak x-show="visible" x-transition
         class="fixed inset-x-3 bottom-3 z-50 sm:inset-x-auto sm:right-5 sm:bottom-5 sm:max-w-md" role="region" aria-label="Cookie consent">
        <div class="card p-5 shadow-[var(--shadow-pop)]">
            <p class="font-heading text-lg font-semibold text-ink">Cookies & analytics</p>
            <p class="mt-1.5 text-sm text-muted">We use privacy-friendly statistics by default. With your permission we also use Google Analytics to understand which teaching material is most useful.</p>
            <div class="mt-4 flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" x-on:click="accept()">Accept analytics</button>
                <button type="button" class="btn btn-secondary btn-sm" x-on:click="decline()">Decline</button>
                @if ($privacy = \App\Models\Page::where('slug', 'privacy')->published()->first())
                    <a href="{{ $privacy->urlPath() }}" class="btn btn-ghost btn-sm">Privacy</a>
                @endif
            </div>
        </div>
    </div>
@endif
