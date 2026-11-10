{{-- Live preview of the popup being edited, on a real page of the website (unsaved values; never counted in statistics). --}}
@php($livewire = $getLivewire())
<div class="oa-preview-shell" x-data="{ device: 'desktop', scale: 1, height: 620, widths: { desktop: 1280, tablet: 820, mobile: 390 }, src: null, t: null, mode: 'light',
        wide: true, open: (() => { try { return localStorage.getItem('oa-preview-open') !== '0' } catch (e) { return true } })(),
        togglePreview() { this.open = ! this.open; try { localStorage.setItem('oa-preview-open', this.open ? '1' : '0') } catch (e) {} this.$nextTick(() => this.fit()) },
        previewHeight(chrome) { this.wide = window.matchMedia('(min-width: 80rem)').matches; return this.wide ? Math.max(360, window.innerHeight - chrome) : Math.max(360, Math.round(window.innerHeight * 0.7)) },
        get w() { return this.widths[this.device] },
        fit() { if (! this.$refs.wrap) return; this.height = this.previewHeight(260); const cw = this.$refs.wrap.clientWidth; if (cw) this.scale = Math.min(1, cw / this.w) },
        reload() { const p = $wire.previewPath || '/'; this.src = @js(rtrim(url('/'), '/')) + p + '?{{ \App\Services\Popups\PopupService::PREVIEW_QUERY }}=' + @js($livewire->previewToken) + '&color_mode=' + this.mode + '&t=' + Date.now() },
        queue() { clearTimeout(this.t); this.t = setTimeout(() => $wire.refreshPreview(), 700) } }"
     x-init="fit(); $wire.refreshPreview(); new ResizeObserver(() => fit()).observe($refs.wrap)" x-on:resize.window.debounce.150ms="fit()" x-on:popup-preview-updated.window="reload()"
     x-on:input.window="if (!$el.contains($event.target)) queue()" x-on:change.window="if (!$el.contains($event.target)) queue()"
     x-on:click.window="if (!$el.contains($event.target) && $event.target.closest('.fi-fo-toggle-buttons, .fi-fo-toggle, .fi-fo-checkbox-list, .fi-fo-select, .fi-fo-rich-editor')) queue()">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <button type="button" x-cloak x-show="!wide" x-on:click="togglePreview()" :aria-expanded="open.toString()" class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:bg-white/5 dark:text-gray-200"><span x-text="open ? @js(__('Hide preview')) : @js(__('Show preview'))"></span></button>
        <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
            <template x-for="(px, key) in widths" :key="key">
                <button type="button" x-on:click="device = key; $nextTick(() => fit())" class="rounded-md px-3 py-1.5 text-xs font-semibold" :class="device === key ? 'bg-white text-gray-950 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500'" x-text="{ desktop: @js(__('Desktop')), tablet: @js(__('Tablet')), mobile: @js(__('Mobile')) }[key]"></button>
            </template>
        </div>
        <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5" role="group" aria-label="{{ __('Preview colour mode') }}">
            @foreach (['light' => 'Light', 'dark' => 'Dark'] as $key => $label)
                <button type="button" x-on:click="mode = '{{ $key }}'; reload()" class="rounded-md px-3 py-1.5 text-xs font-semibold" :class="mode === '{{ $key }}' ? 'bg-white text-gray-950 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500'">{{ __($label) }}</button>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="previewPath" x-on:change="$nextTick(() => $wire.refreshPreview())" class="rounded-lg border-gray-200 py-1.5 text-xs dark:border-white/10 dark:bg-white/5" aria-label="{{ __('Preview page') }}">
                @foreach ($livewire->previewPaths() as $path => $label)<option value="{{ $path }}">{{ $label }}</option>@endforeach
            </select>
            <button type="button" x-on:click="$wire.refreshPreview()" class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:bg-white/5 dark:text-gray-200" title="{{ __('Show again') }}">↻ {{ __('Replay') }}</button>
        </div>
    </div>
    <div x-show="wide || open">
    <div x-ref="wrap" class="oa-preview-frame-wrap" :style="`height:${height}px`">
        <div class="mx-auto h-full" :style="`width:${w * scale}px`">
            <iframe :src="src" title="{{ __('Popup preview') }}" class="oa-preview-frame" :style="`width:${w}px;height:${height / scale}px;transform:scale(${scale})`"></iframe>
        </div>
    </div>
    <p class="mt-2 text-center text-xs text-gray-500">{{ __('Shown at once in the preview, whatever the display rules. Save to apply changes on the website.') }}</p>
    </div>
</div>
