@if (setting('header.announcement_enabled') && filled(setting('header.announcement')))
    <div class="bg-ink text-ink-contrast text-sm" x-data="{ open: true }" x-show="open">
        <div class="container-site flex items-center justify-center gap-3 py-2 text-center">
            @if ($url = setting('header.announcement_url'))
                <a href="{{ $url }}" class="font-medium hover:underline underline-offset-4">{{ setting('header.announcement') }} <span aria-hidden="true">→</span></a>
            @else
                <span class="font-medium">{{ setting('header.announcement') }}</span>
            @endif
        </div>
    </div>
@endif
