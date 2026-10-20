@php($livewire = $getLivewire())
<div class="oa-preview-shell" x-data="{ device: 'desktop', scale: 1, height: 760, widths: { desktop: 1280, tablet: 820, mobile: 390 }, src: null, t: null,
        get w() { return this.widths[this.device] },
        fit() { this.scale = Math.min(1, this.$refs.wrap.clientWidth / this.w); this.height = Math.max(520, window.innerHeight - 190) },
        reload() { const p = $wire.previewPath || '/'; this.src = @js(rtrim(url('/'), '/')) + p + '?theme_preview=' + @js($livewire->previewToken) + '&t=' + Date.now() },
        queue() { clearTimeout(this.t); this.t = setTimeout(() => $wire.refreshPreview(), 700) } }"
     x-init="fit(); $wire.refreshPreview(); new ResizeObserver(() => fit()).observe($refs.wrap)" x-on:resize.window.debounce.150ms="fit()" x-on:theme-preview-updated.window="reload()"
     x-on:input.window="if (!$el.contains($event.target)) queue()" x-on:change.window="if (!$el.contains($event.target)) queue()" x-on:click.window="if (!$el.contains($event.target) && $event.target.closest('.fi-fo-toggle-buttons, .fi-fo-toggle')) queue()">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
            <template x-for="(px, key) in widths" :key="key">
                <button type="button" x-on:click="device = key; $nextTick(() => fit())" class="rounded-md px-3 py-1.5 text-xs font-semibold capitalize" :class="device === key ? 'bg-white text-gray-950 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500'" x-text="key"></button>
            </template>
        </div>
        <select wire:model.live="previewPath" x-on:change="$nextTick(() => $wire.refreshPreview())" class="rounded-lg border-gray-200 py-1.5 text-xs dark:border-white/10 dark:bg-white/5">
            @foreach ($livewire->previewPaths() as $path => $label)<option value="{{ $path }}">{{ $label }}</option>@endforeach
        </select>
    </div>
    <div x-ref="wrap" class="oa-preview-frame-wrap" :style="`height:${height}px`">
        <div class="mx-auto h-full" :style="`width:${w * scale}px`">
            <iframe :src="src" title="Theme preview" class="oa-preview-frame" :style="`width:${w}px;height:${height / scale}px;transform:scale(${scale})`"></iframe>
        </div>
    </div>
    <p class="mt-2 text-center text-xs text-gray-500">Preview of unsaved changes — click “Save changes” to publish them.</p>
</div>
