/**
 * Installable app (PWA): service-worker registration, update prompt and install experience.
 * Server-side configuration comes from window.__pwa (layouts/app). See App\Services\Pwa\PwaService.
 */
import { track } from './analytics';

const cfg = window.__pwa || { enabled: false };
const storage = {
    get(key) { try { return localStorage.getItem(key); } catch (e) { return null; } },
    set(key, value) { try { localStorage.setItem(key, value); } catch (e) { /* private mode */ } },
};

const ua = navigator.userAgent;
const isIos = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const isPhone = () => window.matchMedia('(pointer: coarse) and (max-width: 900px)').matches;

// Chromium fires this early (Android, desktop Chrome/Edge): keep it for our own install button.
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    window.Alpine?.store('pwa') && window.Alpine.store('pwa').setDeferred(event);
});
window.addEventListener('appinstalled', () => {
    window.Alpine?.store('pwa') && (window.Alpine.store('pwa').installed = true);
    track('pwa_installed');
});

document.addEventListener('alpine:init', () => {
    window.Alpine.store('pwa', {
        enabled: !!cfg.enabled,
        installed: isStandalone(),
        ios: isIos,
        deferred: deferredPrompt,
        suggest: false,
        iosHelp: false,
        updateReady: false,
        waiting: null,
        get canInstall() {
            return this.enabled && !this.installed && (!!this.deferred || this.ios);
        },
        setDeferred(event) {
            this.deferred = event;
            maybeSuggest();
        },
        async install(source = 'button') {
            this.suggest = false;
            if (this.deferred) {
                const prompt = this.deferred;
                this.deferred = null;
                prompt.prompt();
                const { outcome } = await prompt.userChoice;
                track('pwa_install_prompt', { outcome, source });
                if (outcome === 'accepted') this.installed = true;
                else storage.set('oa-pwa-dismissed', String(Date.now()));
            } else if (this.ios) {
                this.iosHelp = true;
                track('pwa_ios_help', { source });
            }
        },
        dismiss() {
            this.suggest = false;
            storage.set('oa-pwa-dismissed', String(Date.now()));
            track('pwa_suggestion_dismissed');
        },
        refresh() {
            wantsReload = true;
            if (this.waiting) this.waiting.postMessage({ type: 'SKIP_WAITING' });
            else window.location.reload();
        },
    });
});

// --------------------------------------------------------------------- install suggestion (phones)
// Shown once the visitor is engaged (third page view), only on phones, never when installed,
// when the browser cannot install, while the cookie banner is open, or within 30 days of “Not now”.
const views = Number(storage.get('oa-pwa-views') || 0) + 1;
storage.set('oa-pwa-views', String(views));
let suggestTimer = null;

function maybeSuggest() {
    const store = window.Alpine?.store('pwa');
    if (!store || !cfg.suggest || store.suggest || suggestTimer) return;
    const dismissed = Number(storage.get('oa-pwa-dismissed') || 0);
    if (!store.canInstall || !isPhone() || views < 3 || Date.now() - dismissed < 30 * 864e5) return;
    suggestTimer = setTimeout(() => {
        const consent = document.querySelector('[aria-label="Cookie consent"]');
        if (consent && consent.offsetParent !== null) { suggestTimer = null; return; }
        if (document.documentElement.dataset.popupOpen) { suggestTimer = null; return; }
        store.suggest = true;
        track('pwa_suggestion_shown');
    }, 4000);
}
document.addEventListener('alpine:initialized', maybeSuggest);

// --------------------------------------------------------------------- service worker
let wantsReload = false;

async function register() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return; // needs HTTPS (or localhost)

    if (!cfg.enabled) {
        // App switched off: remove any worker installed earlier.
        for (const reg of await navigator.serviceWorker.getRegistrations()) await reg.unregister();
        return;
    }

    const reg = await navigator.serviceWorker.register(cfg.worker, { scope: '/', updateViaCache: 'none' });
    window.oaPwa = { registration: reg }; // entry point for future features (e.g. push subscription)

    const ready = (worker) => {
        const store = window.Alpine?.store('pwa');
        if (!store) return;
        store.waiting = worker;
        store.updateReady = true;
    };
    if (reg.waiting && navigator.serviceWorker.controller) ready(reg.waiting);
    reg.addEventListener('updatefound', () => {
        const worker = reg.installing;
        worker?.addEventListener('statechange', () => {
            if (worker.state === 'installed' && navigator.serviceWorker.controller) ready(worker);
        });
    });
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (wantsReload) { wantsReload = false; window.location.reload(); }
    });

    // Look for a new version regularly and whenever the app comes back to the foreground.
    let lastCheck = Date.now();
    const check = () => { lastCheck = Date.now(); reg.update().catch(() => {}); };
    setInterval(check, 60 * 60 * 1000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && Date.now() - lastCheck > 15 * 60 * 1000) check();
    });

    // Signing in or out: forget pages saved for offline reading on this device.
    const auth = cfg.auth ? '1' : '0';
    if (storage.get('oa-pwa-auth') !== null && storage.get('oa-pwa-auth') !== auth) {
        navigator.serviceWorker.controller?.postMessage({ type: 'CLEAR_PAGES' });
    }
    storage.set('oa-pwa-auth', auth);
}

if (document.readyState === 'complete') register();
else window.addEventListener('load', register);
