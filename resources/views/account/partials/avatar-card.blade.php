@php $hasPhoto = filled($user->avatar_path); @endphp
<section class="card p-6" aria-labelledby="photo-heading"
         x-data="avatarEditor({ uploadUrl: @js(route('account.avatar.store')), userId: {{ $user->id }}, hasPhoto: @js($hasPhoto), minPx: {{ \App\Services\Users\AvatarService::MIN_PX }} })"
         x-on:keydown.escape.window="cropping && cancel()">
    <h2 id="photo-heading" class="font-heading text-xl font-semibold text-ink">Profile photo</h2>
    <div class="mt-4 flex items-center gap-5">
        <x-avatar :user="$user" size="xl" class="ring-1 ring-line" />
        <div class="min-w-0 space-y-2">
            {{-- Works without JavaScript too: choose a file and press Upload (it is cropped to the centre). --}}
            <form method="post" action="{{ route('account.avatar.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                @csrf
                <label class="btn btn-secondary btn-sm cursor-pointer" :class="busy && 'pointer-events-none opacity-60'">
                    <x-heroicon-o-camera />
                    <span x-text="hasPhoto ? 'Change photo' : 'Upload photo'">{{ $hasPhoto ? 'Change photo' : 'Upload photo' }}</span>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:change="pick($event)">
                </label>
                <button type="submit" class="btn btn-primary btn-sm [.js_&]:hidden">Upload</button>
            </form>
            <form method="post" action="{{ route('account.avatar.destroy') }}" x-show="hasPhoto" @unless ($hasPhoto) style="display:none" @endunless x-on:submit.prevent="remove()">
                @csrf @method('delete')
                <button type="submit" class="text-sm font-medium text-muted hover:text-red-700" :disabled="busy">Remove photo</button>
            </form>
        </div>
    </div>
    <p class="field-hint mt-4">JPG, PNG or WebP, at least {{ \App\Services\Users\AvatarService::MIN_PX }} × {{ \App\Services\Users\AvatarService::MIN_PX }} px, up to 5 MB. Shown with your comments and in your account. Please do not use a photo of a patient.</p>
    @error('avatar')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    <p class="field-error" role="alert" x-show="error" x-text="error" x-cloak></p>
    <p class="mt-2 text-sm text-accent-ink" role="status" x-show="status" x-text="status" x-cloak></p>

    {{-- Crop dialog --}}
    <template x-teleport="body">
        <div x-show="cropping" x-cloak class="fixed inset-0 z-[60] grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="crop-heading">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-on:click="cancel()"></div>
            <div class="card relative w-full max-w-sm p-5 shadow-[var(--shadow-pop)]" x-trap.noscroll="cropping">
                <h3 id="crop-heading" class="font-heading text-lg font-semibold text-ink">Position your photo</h3>
                <p class="mt-1 text-sm text-muted">Drag to move, use the slider or mouse wheel to zoom.</p>
                <div class="relative mx-auto mt-4 aspect-square w-full max-w-72 touch-none overflow-hidden rounded-ui bg-viewer outline-none focus-visible:ring-2 focus-visible:ring-accent"
                     x-ref="stage" tabindex="0" aria-label="Photo position. Use the arrow keys to move and plus or minus to zoom."
                     x-on:pointerdown="down($event)" x-on:pointermove="move($event)" x-on:pointerup="up()" x-on:pointercancel="up()"
                     x-on:wheel.prevent="wheel($event)" x-on:keydown="key($event)">
                    <canvas x-ref="canvas" class="size-full cursor-grab active:cursor-grabbing"></canvas>
                    {{-- Circular guide: what will show in round avatars --}}
                    <div class="pointer-events-none absolute inset-0 rounded-full shadow-[0_0_0_999px_rgb(0_0_0/.5)] ring-2 ring-white/80"></div>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <x-heroicon-o-minus class="size-4 text-muted" />
                    <input type="range" min="1" max="6" step="0.01" :value="factor" x-on:input="setFactor($event.target.value)" class="h-1.5 flex-1 cursor-pointer accent-[var(--color-accent)]" aria-label="Zoom">
                    <x-heroicon-o-plus class="size-4 text-muted" />
                    <button type="button" x-on:click="rotate()" class="ml-1 inline-flex size-9 items-center justify-center rounded-ui text-muted ring-1 ring-line-strong hover:text-ink" aria-label="Rotate 90 degrees" title="Rotate">
                        <x-heroicon-o-arrow-path class="size-4.5" />
                    </button>
                </div>
                <p class="field-error" x-show="error" x-text="error"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" x-on:click="cancel()" class="btn btn-ghost btn-sm" :disabled="busy">Cancel</button>
                    <button type="button" x-on:click="save()" class="btn btn-primary btn-sm" :disabled="busy">
                        <span x-show="!busy">Save photo</span><span x-show="busy" x-cloak>Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</section>
