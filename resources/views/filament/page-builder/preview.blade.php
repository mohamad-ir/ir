@php($livewire = $getLivewire())
<div class="oa-preview-shell"
     x-data="{
        device: 'desktop', scale: 1, height: 760, auto: true, loading: true, src: null, scrollY: 0,
        wide: true, open: (() => { try { return localStorage.getItem('oa-preview-open') !== '0' } catch (e) { return true } })(),
        togglePreview() { this.open = ! this.open; try { localStorage.setItem('oa-preview-open', this.open ? '1' : '0') } catch (e) {} this.$nextTick(() => this.fit()) },
        previewHeight(chrome) { this.wide = window.matchMedia('(min-width: 80rem)').matches; return this.wide ? Math.max(360, window.innerHeight - chrome) : Math.max(360, Math.round(window.innerHeight * 0.7)) },
        widths: { desktop: 1280, tablet: 820, mobile: 390 },
        get deviceWidth() { return this.widths[this.device] },
        fit() {
            this.height = this.previewHeight(200);
            const w = this.$refs.wrap.clientWidth;
            if (w) this.scale = Math.min(1, w / this.deviceWidth);
        },
        reload() {
            try { this.scrollY = this.$refs.frame.contentWindow.scrollY || 0 } catch (e) {}
            this.loading = true;
            this.src = @js($livewire->previewUrl()) + '?t=' + Date.now();
        },
        restoreScroll() {
            this.loading = false;
            try { this.$refs.frame.contentWindow.scrollTo(0, this.scrollY) } catch (e) {}
        },
        queue() { if (this.auto) { clearTimeout(this._t); this._t = setTimeout(() => $wire.refreshPreview(), 1100) } },
     }"
     x-init="fit(); $wire.refreshPreview(); $nextTick(() => fit()); new ResizeObserver(() => fit()).observe($refs.wrap)"
     x-on:resize.window.debounce.150ms="fit()"
     x-on:builder-preview-updated.window="reload()"
     x-on:input.window="if (! $el.contains($event.target)) queue()"
     x-on:change.window="if (! $el.contains($event.target)) queue()"
     x-on:click.window="if (! $el.contains($event.target) && $event.target.closest('.fi-fo-builder')) queue()">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <button type="button" x-cloak x-show="!wide" x-on:click="togglePreview()" :aria-expanded="open.toString()" class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:bg-white/5 dark:text-gray-200"><span x-text="open ? @js(__('Hide preview')) : @js(__('Show preview'))"></span></button>
        <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5" role="group" aria-label="{{ __('Preview device') }}">
            <template x-for="(w, key) in widths" :key="key">
                <button type="button" x-on:click="device = key; $nextTick(() => fit())"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold capitalize transition"
                        :class="device === key ? 'bg-white text-gray-950 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-800'" x-text="{ desktop: @js(__('Desktop')), tablet: @js(__('Tablet')), mobile: @js(__('Mobile')) }[key]"></button>
            </template>
        </div>
        <div class="flex items-center gap-3 text-xs text-gray-500">
            <label class="inline-flex cursor-pointer items-center gap-1.5"><input type="checkbox" x-model="auto" class="rounded border-gray-300 text-primary-600"> {{ __('Auto-refresh') }}</label>
            <x-filament::link tag="button" size="sm" icon="heroicon-m-arrow-path" x-on:click="$wire.refreshPreview()">{{ __('Refresh') }}</x-filament::link>
            <x-filament::link size="sm" icon="heroicon-m-arrow-top-right-on-square" :href="$livewire->previewUrl()" target="_blank">{{ __('Open') }}</x-filament::link>
        </div>
    </div>
    <div x-show="wide || open">
    <div x-ref="wrap" class="oa-preview-frame-wrap relative" :style="`height:${height}px`">
        <div x-show="loading" class="absolute inset-x-0 top-0 z-10 h-0.5 overflow-hidden bg-primary-100"><div class="h-full w-1/3 animate-pulse bg-primary-500"></div></div>
        <div class="mx-auto h-full" :style="`width:${deviceWidth * scale}px`">
            <iframe x-ref="frame" :src="src" x-on:load="restoreScroll()" title="{{ __('Live page preview') }}" class="oa-preview-frame"
                    :style="`width:${deviceWidth}px;height:${height / scale}px;transform:scale(${scale})`"></iframe>
        </div>
    </div>
    <p class="mt-2 text-center text-xs text-gray-500">{{ __('Live preview of unsaved changes ·') }} <bdi dir="ltr"><span x-text="deviceWidth"></span> px</bdi></p>
    </div>
</div>
