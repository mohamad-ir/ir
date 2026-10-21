import { track } from './analytics';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /* ---------------- Search palette ---------------- */
    Alpine.data('searchPalette', (suggestUrl, searchUrl) => ({
        open: false, query: '', results: [], active: -1, loading: false, searched: false, controller: null,
        show() {
            this.open = true;
            document.documentElement.classList.add('overflow-hidden');
            this.$nextTick(() => this.$refs.input?.focus());
        },
        close() {
            this.open = false;
            document.documentElement.classList.remove('overflow-hidden');
        },
        move(step) {
            if (!this.results.length) return;
            this.active = (this.active + step + this.results.length) % this.results.length;
        },
        go(url) { window.location.href = url; },
        async fetchResults() {
            const q = this.query.trim();
            if (q.length < 2) { this.results = []; this.searched = false; return; }
            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;
            try {
                const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(q)}`, { signal: this.controller.signal, headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.results = data.results || [];
                this.active = this.results.length ? 0 : -1;
                this.searched = true;
            } catch (e) {
                if (e.name !== 'AbortError') this.results = [];
            } finally {
                this.loading = false;
            }
        },
    }));

    /* ---------------- OCT image viewer (lightbox with zoom, pan, contrast, invert) ---------------- */
    Alpine.data('caseViewer', (images = []) => ({
        images, index: 0, open: false, scale: 1, x: 0, y: 0, dragging: false, start: null,
        invert: false, contrast: 100, brightness: 100,
        get current() { return this.images[this.index] || {}; },
        get filter() { return `contrast(${this.contrast}%) brightness(${this.brightness}%) ${this.invert ? 'invert(1)' : ''}`; },
        show(i = 0) {
            this.index = i; this.reset(); this.open = true;
            document.documentElement.classList.add('overflow-hidden');
            track('image_zoom', { index: i });
        },
        close() { this.open = false; document.documentElement.classList.remove('overflow-hidden'); },
        reset() { this.scale = 1; this.x = 0; this.y = 0; },
        resetTone() { this.invert = false; this.contrast = 100; this.brightness = 100; },
        next() { this.index = (this.index + 1) % this.images.length; this.reset(); },
        prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; this.reset(); },
        zoom(delta) { this.scale = Math.min(6, Math.max(1, +(this.scale + delta).toFixed(2))); if (this.scale === 1) { this.x = 0; this.y = 0; } },
        wheel(e) { this.zoom(e.deltaY < 0 ? 0.25 : -0.25); },
        down(e) { if (this.scale === 1) return; this.dragging = true; this.start = { x: e.clientX - this.x, y: e.clientY - this.y }; },
        moveDrag(e) { if (!this.dragging) return; this.x = e.clientX - this.start.x; this.y = e.clientY - this.start.y; },
        up() { this.dragging = false; },
        key(e) {
            if (!this.open) return;
            if (e.key === 'ArrowRight') this.next();
            if (e.key === 'ArrowLeft') this.prev();
            if (e.key === '+' || e.key === '=') this.zoom(0.5);
            if (e.key === '-') this.zoom(-0.5);
            if (e.key === 'i') this.invert = !this.invert;
            if (e.key === '0') this.reset();
        },
    }));

    /* ---------------- Before / after comparison slider ---------------- */
    Alpine.data('compareSlider', () => ({
        pos: 50, dragging: false,
        set(e) {
            const rect = this.$refs.frame.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            this.pos = Math.min(100, Math.max(0, ((clientX - rect.left) / rect.width) * 100));
        },
        keys(e) {
            if (e.key === 'ArrowLeft') this.pos = Math.max(0, this.pos - 5);
            if (e.key === 'ArrowRight') this.pos = Math.min(100, this.pos + 5);
        },
    }));

    /* ---------------- Case quiz & reveal ---------------- */
    Alpine.data('caseQuiz', (caseId, storageKey) => ({
        selected: null, submitted: false, revealed: false, correct: null,
        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
                if (saved) { this.selected = saved.selected; this.submitted = saved.submitted; this.revealed = saved.revealed; this.correct = saved.correct; }
            } catch (e) { /* ignore */ }
        },
        persist() {
            try { localStorage.setItem(storageKey, JSON.stringify({ selected: this.selected, submitted: this.submitted, revealed: this.revealed, correct: this.correct })); } catch (e) { /* ignore */ }
        },
        submit(isCorrect) {
            if (this.selected === null) return;
            this.submitted = true;
            this.correct = isCorrect;
            track('case_guess', { option: this.selected, correct: !!isCorrect }, { type: 'case', id: caseId });
            this.reveal(false);
        },
        reveal(record = true) {
            if (!this.revealed) {
                this.revealed = true;
                track('case_reveal', { guessed: this.submitted }, { type: 'case', id: caseId });
            }
            this.persist();
            this.$nextTick(() => document.getElementById('answer')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },
        restart() {
            this.selected = null; this.submitted = false; this.revealed = false; this.correct = null; this.persist();
            window.scrollTo({ top: this.$root.offsetTop - 100, behavior: 'smooth' });
        },
    }));

    /* ---------------- Video player with engagement tracking ---------------- */
    Alpine.data('videoPlayer', (config) => ({
        playing: false, milestones: {}, timer: null, player: null,
        play(startAt = null) {
            this.playing = true;
            track('video_play', { provider: config.provider }, { type: 'video', id: config.id });
            this.$nextTick(() => this.attach(startAt));
        },
        mark(fraction) {
            [25, 50, 75, 100].forEach((m) => {
                if (fraction * 100 >= (m === 100 ? 97 : m) && !this.milestones[m]) {
                    this.milestones[m] = true;
                    track(m === 100 ? 'video_complete' : 'video_progress', { percent: m }, { type: 'video', id: config.id });
                }
            });
        },
        seek(seconds) {
            if (!this.playing) { this.play(seconds); return; }
            if (config.provider === 'youtube' && this.player?.seekTo) this.player.seekTo(seconds, true);
            else if (config.provider === 'vimeo') this.$refs.frame?.contentWindow?.postMessage(JSON.stringify({ method: 'setCurrentTime', value: seconds }), '*');
            else if (this.$refs.video) { this.$refs.video.currentTime = seconds; this.$refs.video.play(); }
        },
        attach(startAt) {
            if (config.provider === 'youtube') {
                const ready = () => {
                    this.player = new window.YT.Player(this.$refs.frame, {
                        events: {
                            onReady: (e) => { if (startAt) e.target.seekTo(startAt, true); },
                            onStateChange: (e) => {
                                clearInterval(this.timer);
                                if (e.data === 1) {
                                    this.timer = setInterval(() => {
                                        const d = this.player.getDuration();
                                        if (d) this.mark(this.player.getCurrentTime() / d);
                                    }, 4000);
                                }
                                if (e.data === 0) this.mark(1);
                            },
                        },
                    });
                };
                if (window.YT && window.YT.Player) ready();
                else {
                    const prev = window.onYouTubeIframeAPIReady;
                    window.onYouTubeIframeAPIReady = () => { prev && prev(); ready(); };
                    if (!document.getElementById('yt-api')) {
                        const s = document.createElement('script');
                        s.id = 'yt-api'; s.src = 'https://www.youtube.com/iframe_api';
                        document.head.appendChild(s);
                    }
                }
            } else if (config.provider === 'vimeo') {
                const frame = this.$refs.frame;
                const post = (method, value) => frame.contentWindow?.postMessage(JSON.stringify(value === undefined ? { method } : { method, value }), '*');
                window.addEventListener('message', (e) => {
                    if (!/vimeo\.com$/.test(new URL(e.origin).hostname)) return;
                    let data; try { data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data; } catch (err) { return; }
                    if (data.event === 'ready') {
                        post('addEventListener', 'playProgress'); post('addEventListener', 'finish');
                        if (startAt) post('setCurrentTime', startAt);
                    }
                    if (data.event === 'playProgress' && data.data) this.mark(data.data.percent);
                    if (data.event === 'finish') this.mark(1);
                });
            } else if (this.$refs.video) {
                const v = this.$refs.video;
                if (startAt) v.currentTime = startAt;
                v.addEventListener('timeupdate', () => v.duration && this.mark(v.currentTime / v.duration));
                v.play().catch(() => {});
            }
        },
    }));

    /* ---------------- Share ---------------- */
    Alpine.data('share', (url, title) => ({
        copied: false,
        async copy() {
            try { await navigator.clipboard.writeText(url); this.copied = true; setTimeout(() => (this.copied = false), 2000); track('share', { network: 'copy' }); } catch (e) { /* ignore */ }
        },
        get canNative() { return !!navigator.share; },
        native() { navigator.share({ url, title }).then(() => track('share', { network: 'native' })).catch(() => {}); },
    }));

    /* ---------------- Table of contents scroll-spy ---------------- */
    Alpine.data('tocSpy', () => ({
        active: null,
        init() {
            const headings = [...document.querySelectorAll('[data-toc-root] h2[id], [data-toc-root] h3[id], [data-toc-section]')];
            if (!headings.length) return;
            const obs = new IntersectionObserver((entries) => {
                entries.forEach((e) => { if (e.isIntersecting) this.active = e.target.id; });
            }, { rootMargin: '-15% 0px -70% 0px' });
            headings.forEach((h) => obs.observe(h));
        },
    }));

    /* ---------------- Cookie consent for optional Google Analytics ---------------- */
    Alpine.data('cookieConsent', (measurementId) => ({
        visible: false,
        init() {
            let choice = null;
            try { choice = localStorage.getItem('oa-consent'); } catch (e) { /* ignore */ }
            if (choice === 'granted') this.load();
            else if (choice !== 'denied') setTimeout(() => (this.visible = true), 800);
        },
        accept() { try { localStorage.setItem('oa-consent', 'granted'); } catch (e) {} this.visible = false; this.load(); },
        decline() { try { localStorage.setItem('oa-consent', 'denied'); } catch (e) {} this.visible = false; },
        load() {
            if (!measurementId || window.gtag) return;
            const s = document.createElement('script');
            s.async = true; s.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', measurementId, { anonymize_ip: true });
        },
    }));

    /* ---------------- Webinar countdown ---------------- */
    Alpine.data('countdown', (iso) => ({
        label: '', timer: null,
        init() { this.tick(); this.timer = setInterval(() => this.tick(), 1000); },
        destroy() { clearInterval(this.timer); },
        tick() {
            const ms = new Date(iso) - new Date();
            if (ms <= 30 * 60 * 1000) {
                clearInterval(this.timer);
                this.label = 'Starting soon';
                // Reload once so the server can show the join button (never loops if clocks differ).
                try { if (!sessionStorage.getItem('cd:' + iso)) { sessionStorage.setItem('cd:' + iso, '1'); window.location.reload(); } } catch (e) {}
                return;
            }
            const d = Math.floor(ms / 864e5), h = Math.floor(ms / 36e5) % 24, m = Math.floor(ms / 6e4) % 60, s = Math.floor(ms / 1e3) % 60;
            this.label = d > 0 ? `${d}d ${h}h ${m}m` : `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },
    }));

    /* ---------------- Photo gallery viewer ---------------- */
    Alpine.data('galleryViewer', (slides = [], deepLink = false) => ({
        slides, deepLink, current: null, zoomed: false, touchX: null, opener: null,
        get slide() { return this.current === null ? null : this.slides[this.current]; },
        init() {
            if (!this.deepLink) return;
            const m = window.location.hash.match(/^#photo-(\d+)$/);
            if (m && this.slides[m[1] - 1]) this.$nextTick(() => this.open(m[1] - 1));
        },
        open(i) {
            this.opener = document.activeElement;
            this.current = i; this.zoomed = false;
            document.documentElement.style.overflow = 'hidden';
            this.sync(); this.preload();
            this.$nextTick(() => this.$refs.closeButton?.focus());
        },
        close() {
            this.current = null; this.zoomed = false;
            document.documentElement.style.overflow = '';
            if (this.deepLink) history.replaceState(null, '', window.location.pathname + window.location.search);
            this.opener?.focus();
        },
        go(i) { this.current = (i + this.slides.length) % this.slides.length; this.zoomed = false; this.sync(); this.preload(); },
        next() { this.go(this.current + 1); },
        prev() { this.go(this.current - 1); },
        key(e) {
            if (this.current === null) return;
            if (e.key === 'Escape') this.close();
            else if (e.key === 'ArrowRight') this.next();
            else if (e.key === 'ArrowLeft') this.prev();
        },
        touchStart(e) { this.touchX = e.changedTouches[0].clientX; },
        touchEnd(e) {
            if (this.touchX === null || this.zoomed) return;
            const dx = e.changedTouches[0].clientX - this.touchX;
            if (Math.abs(dx) > 50) dx < 0 ? this.next() : this.prev();
            this.touchX = null;
        },
        sync() { if (this.deepLink) history.replaceState(null, '', '#photo-' + (this.current + 1)); },
        preload() {
            [this.current + 1, this.current - 1].forEach((i) => {
                const s = this.slides[(i + this.slides.length) % this.slides.length];
                if (s) { const img = new Image(); img.src = s.src; }
            });
        },
    }));
});

/* Reveal-on-scroll animations */
const revealObserver = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
    entries.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); revealObserver.unobserve(e.target); }
    });
}, { rootMargin: '0px 0px -8% 0px' }) : null;

function bindReveal() {
    document.querySelectorAll('.reveal-on-scroll:not(.is-visible)').forEach((el) => {
        if (revealObserver) revealObserver.observe(el); else el.classList.add('is-visible');
    });
}
document.addEventListener('DOMContentLoaded', bindReveal);
document.addEventListener('livewire:navigated', bindReveal);
window.addEventListener('message', (e) => { if (e.data === 'oa-preview-refresh') bindReveal(); });
