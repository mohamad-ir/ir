/**
 * First-party, cookieless analytics beacon. The server derives a daily-rotating
 * anonymous visitor hash; no cookies or personal data are stored client-side.
 */
const cfg = () => window.__oa;

function send(url, payload) {
    const body = JSON.stringify(payload);
    try {
        if (navigator.sendBeacon && navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }))) {
            return;
        }
    } catch (e) { /* fall through */ }
    fetch(url, { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'same-origin' }).catch(() => {});
}

export function track(name, properties = {}, subject = null) {
    const c = cfg();
    if (!c) return;
    send(c.event, { n: name, p: properties, s: subject ?? c.subject, u: location.pathname });
}

window.oaTrack = track;

function pageview() {
    const c = cfg();
    if (!c) return;
    if (navigator.webdriver && !window.__oaAllowBots) return;
    const params = new URLSearchParams(location.search);
    send(c.endpoint, {
        u: location.pathname,
        r: document.referrer || null,
        w: window.innerWidth,
        s: c.subject,
        us: params.get('utm_source'),
        um: params.get('utm_medium'),
        uc: params.get('utm_campaign'),
    });
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(pageview, 0);
} else {
    document.addEventListener('DOMContentLoaded', pageview);
}

// Outbound links and share clicks
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    if (a.dataset.share) {
        track('share', { network: a.dataset.share });
    } else if (a.host && a.host !== location.host && /^https?:/.test(a.protocol)) {
        track('outbound', { url: a.href.slice(0, 300) });
    }
});
