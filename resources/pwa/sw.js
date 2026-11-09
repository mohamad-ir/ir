/*
 * OCT Academy service worker. Served by Laravel at /sw.js (PwaController) with its configuration
 * and version filled in. Every response the app shell needs is versioned, so a new release replaces
 * the caches as a whole and never mixes old pages with new styles.
 *
 * What is cached
 *   - App shell (precache): offline page, main CSS/JS, icons.
 *   - Pages: network first. Only pages the server marks "X-Offline-Cacheable: 1" (public pages viewed
 *     as a guest) are kept, the last CONFIG.pageLimit of them, for reading offline.
 *   - Built assets (/build, hashed): cache first.   Icons, fonts, Livewire script: stale-while-revalidate.
 *   - Public images (/media): stale-while-revalidate, limited to CONFIG.mediaLimit entries.
 * What is never cached: anything not GET, other origins, range requests, every private path
 * (dashboard, account, sign-in, checkout, APIs, lessons, webinar join links, avatars…).
 */
const CONFIG = __CONFIG__;
const VERSION = CONFIG.version;
const CACHE = {
    shell: `oa-shell-${VERSION}`,
    pages: `oa-pages-${VERSION}`,
    assets: `oa-assets-${VERSION}`,
    media: 'oa-media-v1',
};
const PAGE_INDEX = '/__pwa/pages.json';

// ---------------------------------------------------------------------------- lifecycle

self.addEventListener('install', (event) => {
    if (!CONFIG.enabled) {
        self.skipWaiting();
        return;
    }
    event.waitUntil(
        caches.open(CACHE.shell).then(async (cache) => {
            const request = (url) => new Request(url, { cache: 'reload', credentials: 'same-origin' });
            // The offline page is required; every other file is added on its own, so one missing
            // file (e.g. an icon folder not uploaded) never stops the app from installing.
            await cache.add(request(CONFIG.offlineUrl));
            await Promise.allSettled(CONFIG.precache.filter((url) => url !== CONFIG.offlineUrl).map((url) => cache.add(request(url))));
        }),
    );
    // The new version waits until the visitor accepts the update prompt (see resources/js/pwa.js),
    // except on the very first install where nothing is controlled yet.
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keep = new Set(Object.values(CACHE));
        for (const key of await caches.keys()) {
            if (key.startsWith('oa-') && (!keep.has(key) || !CONFIG.enabled)) {
                await caches.delete(key);
            }
        }
        if (!CONFIG.enabled) {
            // Kill switch: the app was switched off in the dashboard.
            await self.registration.unregister();
            for (const client of await self.clients.matchAll({ type: 'window' })) client.navigate(client.url);
            return;
        }
        if (self.registration.navigationPreload) await self.registration.navigationPreload.enable();
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    const type = event.data && event.data.type;
    if (type === 'SKIP_WAITING') self.skipWaiting();
    if (type === 'CLEAR_PAGES') event.waitUntil(caches.delete(CACHE.pages));
    if (type === 'GET_VERSION' && event.ports[0]) event.ports[0].postMessage({ version: VERSION });
});

// ---------------------------------------------------------------------------- routing

function isPrivate(path) {
    for (const prefix of CONFIG.privatePrefixes) {
        const base = prefix.replace(/\/$/, '');
        if (path === base || path.startsWith(base + '/')) return true;
    }
    return CONFIG.privatePatterns.some((pattern) => new RegExp(pattern).test(path));
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET' || request.headers.has('range')) return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (url.pathname === PAGE_INDEX) {
        event.respondWith(pageIndex());
        return;
    }
    if (url.pathname === '/sw.js' || url.pathname === '/manifest.webmanifest') return;

    if (/^\/livewire[^/]*\/livewire(\.min)?\.js$/.test(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request, CACHE.assets));
        return;
    }
    if (isPrivate(url.pathname)) {
        // Straight to the network and never stored. Offline, a private page shows the generic offline page.
        if (request.mode === 'navigate') {
            // Use the navigation-preload response when there is one, so the page is requested only once.
            event.respondWith((async () => {
                try {
                    return (await event.preloadResponse) || (await fetch(request));
                } catch (e) {
                    return (await caches.match(CONFIG.offlineUrl)) || Response.error();
                }
            })());
        }
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstPage(event));
        return;
    }
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request, CACHE.assets));
        return;
    }
    if (url.pathname.startsWith('/icons/') || url.pathname.startsWith('/fonts/') || url.pathname === '/favicon.svg') {
        event.respondWith(staleWhileRevalidate(request, CACHE.assets));
        return;
    }
    if (url.pathname.startsWith('/media/') && request.destination === 'image') {
        event.respondWith(staleWhileRevalidate(request, CACHE.media, CONFIG.mediaLimit));
    }
});

// ---------------------------------------------------------------------------- strategies

function storable(response) {
    return response && response.ok && response.type === 'basic' && !/no-store/i.test(response.headers.get('Cache-Control') || '');
}

async function networkFirstPage(event) {
    const request = event.request;
    try {
        const response = (await event.preloadResponse) || (await fetch(request));
        // Only guest views of public pages are stored (the server decides, see MarkOfflineCacheable).
        // The explicit opt-in header decides for pages (their HTTP "no-store" only concerns shared HTTP caches).
        if (response.ok && response.type === 'basic' && !response.redirected && response.headers.get('X-Offline-Cacheable') === '1') {
            event.waitUntil(savePage(request, response.clone()));
        }
        return response;
    } catch (error) {
        const cache = await caches.open(CACHE.pages);
        const saved = (await cache.match(request)) || (await cache.match(request.url.split('?')[0]));
        if (saved) return saved;
        return (await caches.match(CONFIG.offlineUrl)) || new Response('You are offline.', { status: 503, headers: { 'Content-Type': 'text/plain' } });
    }
}

async function savePage(request, response) {
    const cache = await caches.open(CACHE.pages);
    const html = await response.clone().text();
    const title = (html.match(/<title>([^<]*)<\/title>/i) || [])[1] || new URL(request.url).pathname;
    await cache.put(request, response);
    const index = await readIndex(cache);
    const path = new URL(request.url).pathname + new URL(request.url).search;
    index.pages = [{ url: path, title: decode(title), savedAt: Date.now() }, ...index.pages.filter((p) => p.url !== path)];
    // Keep the most recent pages only.
    for (const old of index.pages.splice(CONFIG.pageLimit)) await cache.delete(old.url);
    await cache.put(PAGE_INDEX, new Response(JSON.stringify(index), { headers: { 'Content-Type': 'application/json' } }));
}

async function readIndex(cache) {
    const res = await cache.match(PAGE_INDEX);
    return res ? res.json() : { pages: [] };
}

async function pageIndex() {
    const index = await readIndex(await caches.open(CACHE.pages));
    return new Response(JSON.stringify(index), { headers: { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' } });
}

function decode(text) {
    return text.replace(/&amp;/g, '&').replace(/&#039;|&#39;/g, "'").replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&mdash;/g, '—').trim();
}

async function cacheFirst(request, name) {
    const cache = await caches.open(name);
    const cached = await cache.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (storable(response)) cache.put(request, response.clone());
    return response;
}

async function staleWhileRevalidate(request, name, limit) {
    const cache = await caches.open(name);
    const cached = await cache.match(request);
    const network = fetch(request).then(async (response) => {
        if (storable(response)) {
            await cache.put(request, response.clone());
            if (limit) await trim(cache, limit);
        }
        return response;
    }).catch(() => cached);
    return cached || network;
}

async function trim(cache, limit) {
    const keys = await cache.keys();
    for (const key of keys.slice(0, Math.max(0, keys.length - limit))) await cache.delete(key);
}

// ---------------------------------------------------------------------------- notifications (prepared)
// Push notifications are not switched on yet. When they are, the server will send a JSON payload
// { title, body, url, tag } signed with VAPID keys; this handler already shows it safely, and
// clicking opens the URL (same origin only). No subscription is created until that feature exists.

self.addEventListener('push', (event) => {
    if (!event.data) return;
    let data = {};
    try { data = event.data.json(); } catch (e) { return; }
    const target = new URL(data.url || '/', self.location.origin);
    event.waitUntil(self.registration.showNotification(data.title || 'OCT Academy', {
        body: data.body || '',
        tag: data.tag,
        icon: '/icons/icon-192.png',
        badge: '/icons/favicon-48.png',
        data: { url: target.origin === self.location.origin ? target.href : self.location.origin + '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil((async () => {
        for (const client of await self.clients.matchAll({ type: 'window', includeUncontrolled: true })) {
            if (client.url === url && 'focus' in client) return client.focus();
        }
        return self.clients.openWindow(url);
    })());
});
