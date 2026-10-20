<div>
    @if ($result)
        <div @class(['flex items-start gap-3 rounded-card p-4 text-sm', 'bg-white/10 text-white' => $dark, 'bg-accent/10 text-fg ring-1 ring-accent/20' => ! $dark, 'mt-4' => $variant === 'footer']) role="status">
            <x-heroicon-o-envelope-open class="size-5 shrink-0 {{ $dark ? 'text-white/80' : 'text-accent-ink' }}" />
            <p>
                @if ($result === 'pending')
                    <strong>Almost there!</strong> Please check your inbox and click the confirmation link.
                @elseif ($result === 'already')
                    You are already subscribed — thank you!
                @else
                    <strong>Subscribed.</strong> Welcome aboard.
                @endif
            </p>
        </div>
    @else
        <form wire:submit="subscribe" @class(['mt-4' => $variant === 'footer'])>
            <div class="hidden" aria-hidden="true"><label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>
            @if (! $compact && $variant !== 'footer')
                <label for="nl-name-{{ $this->getId() }}" class="sr-only">First name</label>
                <input id="nl-name-{{ $this->getId() }}" type="text" wire:model="name" class="input mb-3" placeholder="First name (optional)" autocomplete="given-name">
            @endif
            <div @class(['flex gap-2', 'flex-col sm:flex-row' => $variant !== 'footer', 'flex-col' => $variant === 'footer'])>
                <label for="nl-email-{{ $this->getId() }}" class="sr-only">Email address</label>
                <input id="nl-email-{{ $this->getId() }}" type="email" wire:model="email" required autocomplete="email" placeholder="you@hospital.org"
                       @class(['input', '!bg-white/[.08] !text-white !ring-white/15 placeholder:!text-white/40' => $dark])>
                <button type="submit" class="btn {{ $dark ? 'btn-light' : 'btn-primary' }} shrink-0" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                    <span wire:loading wire:target="subscribe">Subscribing…</span>
                </button>
            </div>
            @error('email')<p class="field-error {{ $dark ? '!text-red-300' : '' }}">{{ $message }}</p>@enderror
            <p class="mt-2.5 text-xs {{ $dark ? 'text-white/45' : 'text-muted' }}">Free. Unsubscribe with one click. We never share your address.</p>
        </form>
    @endif
</div>
