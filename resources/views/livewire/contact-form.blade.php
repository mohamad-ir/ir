<div>
    @if ($sent)
        <div class="py-10 text-center" role="status">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-accent/10 text-accent-ink"><x-heroicon-o-paper-airplane class="size-7" /></span>
            <p class="mt-5 font-heading text-2xl font-semibold text-ink">Thank you — message received</p>
            <p class="mx-auto mt-2 max-w-sm text-muted">We read every message and usually reply within a few working days.</p>
        </div>
    @else
        <form wire:submit="submit" class="space-y-5" novalidate>
            <div class="hidden" aria-hidden="true"><label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>
            <div>
                <label for="c-type" class="field-label">Type of enquiry</label>
                <select id="c-type" wire:model="type" class="input">
                    @foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="c-name" class="field-label">Your name</label>
                    <input id="c-name" type="text" wire:model="name" class="input" autocomplete="name" required>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="c-email" class="field-label">Email</label>
                    <input id="c-email" type="email" wire:model="email" class="input" autocomplete="email" required>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="c-org" class="field-label">Institution / organisation <span class="font-normal text-muted">(optional)</span></label>
                <input id="c-org" type="text" wire:model="organisation" class="input" autocomplete="organization">
            </div>
            <div>
                <label for="c-subject" class="field-label">Subject</label>
                <input id="c-subject" type="text" wire:model="subject" class="input" required>
                @error('subject')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="c-message" class="field-label">Message</label>
                <textarea id="c-message" wire:model="message" rows="6" class="input" required></textarea>
                @error('message')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="consent" class="checkbox mt-0.5">
                <span>I understand this form is for professional enquiries only and not for medical advice about a patient.</span>
            </label>
            @error('consent')<p class="field-error -mt-3">{{ $message }}</p>@enderror

            @if (\App\Support\Turnstile::enabled())
                <div wire:ignore x-data x-init="
                    const render = () => window.turnstile.render($el, { sitekey: @js(setting('security.turnstile_site_key')), callback: (t) => $wire.set('turnstileToken', t, false) });
                    if (window.turnstile) render(); else { const s = document.createElement('script'); s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js'; s.onload = render; document.head.appendChild(s); }"></div>
            @endif

            <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Send message</span>
                <span wire:loading wire:target="submit">Sending…</span>
            </button>
        </form>
    @endif
</div>
