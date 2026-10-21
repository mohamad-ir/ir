@if (! empty($buttons))
    <div class="flex flex-wrap items-center gap-3 {{ $class ?? '' }}">
        @foreach ($buttons as $button)
            @continue(blank($button['label'] ?? null))
            @php
                $style = $button['style'] ?? 'primary';
                $cls = match (true) {
                    $light && $style === 'primary' => 'btn btn-lg btn-light',
                    $light && $style === 'secondary' => 'btn btn-lg btn-outline-light',
                    $light && $style === 'ghost' => 'btn btn-lg text-white/85 hover:text-white',
                    $style === 'accent' => 'btn btn-lg btn-accent',
                    $style === 'secondary' => 'btn btn-lg btn-secondary',
                    $style === 'ghost' => 'link-arrow',
                    default => 'btn btn-lg btn-primary',
                };
                $external = str_starts_with($button['url'] ?? '', 'http') && ! str_starts_with($button['url'], url('/'));
            @endphp
            <a href="{{ $button['url'] ?? '#' }}" class="{{ $cls }}" @if($external) target="_blank" rel="noopener" @endif>
                {{ $button['label'] }}
                @if (in_array($style, ['primary', 'accent', 'ghost']))<x-heroicon-m-arrow-right />@endif
            </a>
        @endforeach
    </div>
@endif
