@props(['url', 'title', 'label' => 'Share'])
<div x-data="share(@js($url), @js($title))" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <span class="mr-1 text-sm font-medium text-muted">{{ $label }}</span>
    @foreach (\App\Support\Social::shareLinks($url, $title) as $network => $link)
        <a href="{{ $link['url'] }}" target="_blank" rel="noopener" data-share="{{ $network }}" class="grid size-9 place-items-center rounded-full bg-surface text-muted ring-1 ring-line transition hover:text-ink hover:ring-line-strong" aria-label="Share on {{ $link['label'] }}">
            @if ($network === 'whatsapp')
                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 0 1 12 2.2c5.4 0 9.8 4.4 9.8 9.8s-4.4 9.8-9.8 9.8zM20.5 3.5A11.8 11.8 0 0 0 1.9 17.7L.2 24l6.4-1.7A11.8 11.8 0 0 0 24 12c0-3.2-1.2-6.2-3.5-8.5z"/></svg>
            @elseif ($network === 'email')
                <x-heroicon-o-envelope class="size-4" />
            @else
                <x-social-icon :network="$network" class="size-4" />
            @endif
        </a>
    @endforeach
    <button type="button" x-on:click="copy()" class="inline-flex h-9 items-center gap-1.5 rounded-full bg-surface px-3.5 text-sm font-medium text-muted ring-1 ring-line transition hover:text-ink hover:ring-line-strong">
        <x-heroicon-o-link class="size-4" /><span x-text="copied ? 'Copied!' : 'Copy link'">Copy link</span>
    </button>
</div>
