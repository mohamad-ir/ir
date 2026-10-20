{{-- Requires an enclosing x-data="caseViewer(images)" --}}
<template x-teleport="body">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[70] flex flex-col bg-black/95 text-white" role="dialog" aria-modal="true" aria-label="Image viewer"
         x-on:keydown.escape.window="close()">
        <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <p class="text-sm text-white/70"><span x-text="index + 1"></span> / <span x-text="images.length"></span> <span class="ml-3 font-medium text-white" x-text="current.label"></span></p>
            <div class="flex items-center gap-1">
                <button type="button" class="rounded-ui p-2 hover:bg-white/10" x-on:click="zoom(-0.5)" aria-label="Zoom out"><x-heroicon-o-magnifying-glass-minus class="size-5" /></button>
                <span class="w-12 text-center text-xs tabular-nums text-white/60" x-text="Math.round(scale * 100) + '%'"></span>
                <button type="button" class="rounded-ui p-2 hover:bg-white/10" x-on:click="zoom(0.5)" aria-label="Zoom in"><x-heroicon-o-magnifying-glass-plus class="size-5" /></button>
                <span class="mx-2 h-5 w-px bg-white/15"></span>
                <button type="button" class="rounded-ui p-2 hover:bg-white/10" :class="invert && 'bg-white/15'" x-on:click="invert = !invert" aria-label="Invert image" title="Invert (i)"><x-heroicon-o-arrows-right-left class="size-5" /></button>
                <div class="hidden items-center gap-2 px-2 sm:flex" title="Contrast">
                    <x-heroicon-o-adjustments-horizontal class="size-4 text-white/60" />
                    <input type="range" min="60" max="220" x-model="contrast" class="w-24 accent-[var(--color-accent)]" aria-label="Contrast">
                </div>
                <div class="hidden items-center gap-2 px-2 md:flex" title="Brightness">
                    <x-heroicon-o-sun class="size-4 text-white/60" />
                    <input type="range" min="50" max="180" x-model="brightness" class="w-20 accent-[var(--color-accent)]" aria-label="Brightness">
                </div>
                <button type="button" class="rounded-ui px-2 py-1.5 text-xs hover:bg-white/10" x-on:click="reset(); resetTone()">Reset</button>
                <a :href="current.full" target="_blank" rel="noopener" class="rounded-ui p-2 hover:bg-white/10" aria-label="Open original"><x-heroicon-o-arrow-top-right-on-square class="size-5" /></a>
                <button type="button" class="ml-2 rounded-ui p-2 hover:bg-white/10" x-on:click="close()" aria-label="Close viewer"><x-heroicon-o-x-mark class="size-6" /></button>
            </div>
        </div>
        <div class="relative flex-1 overflow-hidden" x-on:wheel.prevent="wheel($event)" x-on:mousedown="down($event)" x-on:mousemove.window="moveDrag($event)" x-on:mouseup.window="up()"
             x-on:dblclick="scale > 1 ? reset() : zoom(1.5)" :class="scale > 1 ? (dragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'">
            <img :src="current.src" :alt="current.alt" draggable="false" class="absolute inset-0 m-auto max-h-full max-w-full select-none object-contain transition-[filter] duration-150"
                 :style="`transform: translate(${x}px, ${y}px) scale(${scale}); filter: ${filter}; transition: ${dragging ? 'none' : 'transform .2s ease'}`">
            <template x-if="images.length > 1">
                <div>
                    <button type="button" class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 backdrop-blur hover:bg-white/20" x-on:click.stop="prev()" aria-label="Previous image"><x-heroicon-m-chevron-left class="size-6" /></button>
                    <button type="button" class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 backdrop-blur hover:bg-white/20" x-on:click.stop="next()" aria-label="Next image"><x-heroicon-m-chevron-right class="size-6" /></button>
                </div>
            </template>
        </div>
        <p class="px-6 py-4 text-center text-sm text-white/75" x-show="current.caption" x-text="current.caption"></p>
        <p class="pb-3 text-center text-[0.7rem] text-white/35 hidden sm:block">Scroll or double-click to zoom · drag to pan · ← → to navigate · i to invert</p>
    </div>
</template>
