/**
 * Popups & announcements — browser side.
 *
 * The server only sends popups whose status, schedule, pages and audience match
 * this request (App\Services\Popups\PopupService). Here we apply the rules only
 * the browser knows: device, first/returning visit, traffic source, trigger,
 * frequency per visitor, the global limits (pause between popups, popups per
 * visit) and priority (one popup per place at a time; the highest priority wins).
 *
 * Per-visitor state lives in localStorage/sessionStorage only (no cookies, nothing personal).
 */
const LS_KEY = 'oa-popups';
const LAST_KEY = 'oa-popups-last';
const VISIT_KEY = 'oa-visit';
const DAY = 864e5;

const store = (area) => {
    try { return window[area]; } catch (e) { return null; }
};
const read = (area, key) => {
    try { return JSON.parse(store(area)?.getItem(key) || 'null') || {}; } catch (e) { return {}; }
};
const write = (area, key, value) => {
    try { store(area)?.setItem(key, JSON.stringify(value)); } catch (e) { /* private mode: rules still work for this page */ }
};

const globals = () => window.__popups || {};

export function deviceType() {
    const w = window.innerWidth;
    if (w < 640) return 'mobile';
    if (w < 1024) return 'tablet';
    return 'desktop';
}

/** The visit: new or returning visitor, where they came from, pages seen and popups shown in this visit. */
const visit = (() => {
    const v = read('sessionStorage', VISIT_KEY);
    if (!v.started) {
        let seen = null;
        try { seen = localStorage.getItem('oa-seen'); localStorage.setItem('oa-seen', String(Date.now())); } catch (e) { /* ignore */ }
        const params = new URLSearchParams(location.search);
        let ref = '';
        try { ref = document.referrer ? new URL(document.referrer).host : ''; } catch (e) { /* ignore */ }
        Object.assign(v, {
            started: Date.now(),
            isNew: !seen,
            source: [ref, params.get('utm_source'), params.get('utm_medium'), params.get('utm_campaign')].filter(Boolean).join(' ').toLowerCase(),
            pages: 0,
            shown: 0,
            ids: [],
        });
    }
    v.pages += 1;
    write('sessionStorage', VISIT_KEY, v);
    return v;
})();

function beacon(id, event) {
    const url = globals().endpoint;
    if (!url || !id) return;
    const body = JSON.stringify({ id, e: event, d: deviceType() });
    try {
        if (navigator.sendBeacon && navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }))) return;
    } catch (e) { /* fall back */ }
    fetch(url, { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'same-origin' }).catch(() => {});
}

const isVisible = (el) => el && el.offsetParent !== null;
const otherOverlayOpen = () => isVisible(document.querySelector('[aria-label="Cookie consent"]'))
    || window.Alpine?.store('pwa')?.suggest || window.Alpine?.store('pwa')?.iosHelp;

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('popups', {
        items: [],
        openSlots: {},
        arranged: false,
        add(popup) {
            this.items.push(popup);
            if (!this.arranged) {
                this.arranged = true;
                setTimeout(() => this.arrange(), 0);
            }
        },
        /** Highest priority eligible popup per place is armed; popups opened by a link are always available. */
        arrange() {
            const bySlot = {};
            this.items
                .filter((p) => p.eligible())
                .sort((a, b) => b.cfg.priority - a.cfg.priority || b.cfg.id - a.cfg.id)
                .forEach((p) => {
                    if (p.cfg.trigger === 'click' || p.cfg.preview) { p.arm(); return; }
                    if (bySlot[p.cfg.slot]) return;
                    bySlot[p.cfg.slot] = p;
                    p.arm();
                });
            this.items.filter((p) => p.cfg.trigger === 'click' && !p.armed).forEach((p) => p.arm());
        },
    });

    Alpine.data('oaPopup', (cfg) => ({
        cfg,
        open: false,
        armed: false,
        done: false,
        timers: [],
        cleanups: [],
        hovering: false,
        returnFocus: null,

        init() {
            this.$store.popups.add(this);
        },

        destroy() {
            this.disarm();
        },

        get key() {
            return `${this.cfg.id}:${this.cfg.rev}`;
        },

        state() {
            return read('localStorage', LS_KEY)[this.key] || {};
        },

        saveState(patch) {
            const all = read('localStorage', LS_KEY);
            all[this.key] = { ...(all[this.key] || {}), ...patch };
            // Forget popups that no longer exist on this page after a year of no activity.
            write('localStorage', LS_KEY, all);
        },

        /** Device, visitor, source, end date and frequency. */
        eligible() {
            const c = this.cfg;
            if (c.preview) return true;
            if (c.ends && Date.now() >= c.ends) return false;
            if (c.devices.length && !c.devices.includes(deviceType())) return false;
            if (c.visitors === 'new' && !visit.isNew) return false;
            if (c.visitors === 'returning' && visit.isNew) return false;
            if (c.source && !visit.source.includes(c.source)) return false;
            if (c.trigger === 'click') return true;

            const s = this.state();
            const now = Date.now();
            if (c.hideAfterGoal && s.g) return false;
            if (c.maxViews && (s.v || 0) >= c.maxViews) return false;
            if (c.afterClose && s.c && now - s.c < c.afterClose * DAY) return false;
            if (c.frequency === 'once' && s.v) return false;
            if (c.frequency === 'session' && visit.ids.includes(c.id)) return false;
            if (c.frequency === 'days' && s.s && now - s.s < c.days * DAY) return false;
            return true;
        },

        later(fn, ms) {
            this.timers.push(setTimeout(fn, ms));
        },

        listen(target, event, fn, options) {
            target.addEventListener(event, fn, options);
            this.cleanups.push(() => target.removeEventListener(event, fn, options));
        },

        disarm() {
            this.timers.forEach(clearTimeout);
            this.timers = [];
            this.cleanups.forEach((fn) => fn());
            this.cleanups = [];
        },

        arm() {
            if (this.armed) return;
            this.armed = true;
            const c = this.cfg;
            const auto = () => this.show(false);

            // Any link to #popup-ID or element with data-open-popup="ID" opens it (always, whatever the trigger).
            this.listen(document, 'click', (e) => {
                const el = e.target.closest?.(`a[href="#popup-${c.id}"], [data-open-popup="${c.id}"]`);
                if (!el) return;
                e.preventDefault();
                this.returnFocus = el;
                this.show(true);
            });

            switch (c.trigger) {
                case 'click':
                    break;
                case 'load':
                    this.later(auto, c.preview ? 150 : 400);
                    break;
                case 'delay':
                    this.later(auto, Math.max(0, c.value || 5) * 1000);
                    break;
                case 'pageviews':
                    if (visit.pages >= Math.max(1, c.value || 2)) this.later(auto, 1500);
                    break;
                case 'scroll': {
                    const target = Math.min(100, Math.max(1, c.value || 50));
                    const check = () => {
                        const max = document.documentElement.scrollHeight - window.innerHeight;
                        if (max <= 50 || (window.scrollY / max) * 100 >= target) auto();
                    };
                    this.listen(window, 'scroll', check, { passive: true });
                    this.later(check, 1500); // short pages that cannot scroll
                    break;
                }
                case 'idle': {
                    let t = null;
                    const reset = () => { clearTimeout(t); t = setTimeout(auto, Math.max(3, c.value || 30) * 1000); };
                    ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach((ev) => this.listen(window, ev, reset, { passive: true }));
                    this.cleanups.push(() => clearTimeout(t));
                    reset();
                    break;
                }
                case 'exit': {
                    const since = Date.now();
                    // Computers: the pointer leaves through the top of the window (towards the tabs or address bar).
                    this.listen(document, 'mouseout', (e) => {
                        if (!e.relatedTarget && e.clientY <= 0 && Date.now() - since > 3000) auto();
                    });
                    // Touch screens: a quick scroll back up after reading part of the page.
                    if (window.matchMedia('(pointer: coarse)').matches) {
                        let lastY = window.scrollY; let lastT = Date.now(); let deepest = 0;
                        this.listen(window, 'scroll', () => {
                            const y = window.scrollY; const now = Date.now();
                            deepest = Math.max(deepest, y);
                            const speed = (lastY - y) / Math.max(1, now - lastT);
                            if (deepest > window.innerHeight && lastY - y > 0 && speed > 1.2 && now - since > 5000) auto();
                            lastY = y; lastT = now;
                        }, { passive: true });
                    }
                    break;
                }
                default:
                    this.later(auto, 5000);
            }
        },

        /** forced = opened by the visitor (link/button); automatic popups respect the global limits. */
        show(forced) {
            if (this.open || (this.done && !forced)) return;
            const c = this.cfg;
            const store = this.$store.popups;
            const counted = !forced && !c.preview && c.type !== 'bar';

            if (counted) {
                const g = globals();
                if (!this.eligible()) return;
                if (g.perSession && visit.shown >= g.perSession) return;
                const last = Number(read('localStorage', LAST_KEY).t || 0);
                if (g.cooldown && Date.now() - last < g.cooldown * 60000) return;
                if (store.openSlots[c.slot] || (c.type === 'modal' && store.openSlots.modal)) return;
                if (otherOverlayOpen()) { this.later(() => this.show(false), 3000); return; }
            }

            this.disarm();
            this.done = true;
            if (!forced) this.returnFocus = document.activeElement;
            store.openSlots[c.slot] = true;
            this.open = true;
            if (c.type === 'modal') document.documentElement.dataset.popupOpen = '1';

            if (!c.preview) {
                const s = this.state();
                this.saveState({ v: (s.v || 0) + 1, s: Date.now() });
                if (!visit.ids.includes(c.id)) visit.ids.push(c.id);
                if (counted) {
                    visit.shown += 1;
                    write('localStorage', LAST_KEY, { t: Date.now() });
                }
                write('sessionStorage', VISIT_KEY, visit);
                beacon(c.id, 'view');
            }

            if (c.slot === 'bar-bottom') this.$nextTick(() => this.liftAbove(true));
            if (c.autoClose) this.scheduleAutoClose();
        },

        /** Corner slide-ins sit above an open bottom bar instead of covering it. */
        liftAbove(on) {
            const root = document.documentElement.style;
            if (on) root.setProperty('--oa-bottom-bar', `${this.$el.offsetHeight}px`);
            else root.removeProperty('--oa-bottom-bar');
        },

        scheduleAutoClose() {
            this.later(() => {
                if (this.hovering) { this.scheduleAutoClose(); return; }
                this.close('auto');
            }, this.cfg.autoClose * 1000);
        },

        close(reason = 'close') {
            if (!this.open) return;
            this.open = false;
            this.disarm();
            delete this.$store.popups.openSlots[this.cfg.slot];
            if (this.cfg.type === 'modal') delete document.documentElement.dataset.popupOpen;
            if (this.cfg.slot === 'bar-bottom') this.liftAbove(false);
            if (!this.cfg.preview && reason !== 'auto' && reason !== 'goal') {
                this.saveState({ c: Date.now() });
                beacon(this.cfg.id, 'close');
            }
            const el = this.returnFocus;
            this.returnFocus = null;
            if (el && typeof el.focus === 'function' && document.contains(el)) {
                this.$nextTick(() => el.focus({ preventScroll: true }));
            }
        },

        overlayClick() {
            if (this.cfg.overlayClose) this.close('overlay');
        },

        /** The main button was used (kind = click) or the form was submitted (kind = convert). */
        goal(kind, event) {
            if (!this.cfg.preview) {
                beacon(this.cfg.id, kind);
                if (this.cfg.hideAfterGoal) this.saveState({ g: Date.now() });
            }
            if (kind === 'click') {
                const a = event?.currentTarget;
                const href = a?.getAttribute('href') || '';
                // Same-page links and new tabs leave the page open, so close the popup ourselves.
                if (href.startsWith('#') || a?.target === '_blank') this.close('goal');
                if (href.startsWith('#popup-')) event.preventDefault();
            } else if (kind === 'convert') {
                this.later(() => this.close('goal'), 4000);
            }
        },

        subscribed(event) {
            if (event.detail?.source === `popup-${this.cfg.id}` && this.open) this.goal('convert');
        },
    }));
});
