@php
    $columns = collect(['footer_primary', 'footer_secondary', 'footer_tertiary'])->map(fn ($l) => $menus[$l] ?? null)->filter();
    $legal = $menus['footer_legal']['items'] ?? [];
    $dark = setting('theme.footer_style', 'dark') === 'dark';
    $social = collect(setting('social') ?: [])->filter();
    $copyright = str_replace(['{year}', '{site}'], [date('Y'), setting('site.name')], setting('footer.copyright') ?? '');
@endphp
<footer @class(['mt-auto', 'bg-ink text-ink-contrast' => $dark, 'bg-subtle border-t border-line' => ! $dark])>
    <div class="container-site pt-16 pb-10">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <x-logo :light="$dark" />
                @if (setting('footer.about'))
                    <p class="mt-5 max-w-sm text-[0.9375rem] leading-relaxed {{ $dark ? 'text-white/65' : 'text-muted' }}">{{ setting('footer.about') }}</p>
                @endif
                @if ($social->isNotEmpty())
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($social as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener me" class="grid size-10 place-items-center rounded-full transition {{ $dark ? 'bg-white/[.07] text-white/80 hover:bg-white/15 hover:text-white' : 'bg-surface ring-1 ring-line text-muted hover:text-ink' }}" aria-label="{{ \App\Support\Social::label($network) }}">
                                <x-social-icon :network="$network" class="size-[1.1rem]" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid gap-10 sm:grid-cols-3 lg:col-span-5">
                @foreach ($columns as $menu)
                    <div>
                        <h2 class="text-xs font-semibold tracking-[.14em] uppercase font-sans {{ $dark ? 'text-white/50' : 'text-muted' }}">{{ $menu['name'] }}</h2>
                        <ul class="mt-4 space-y-2.5" role="list">
                            @foreach ($menu['items'] as $item)
                                <li><a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener" @endif class="text-[0.9375rem] transition {{ $dark ? 'text-white/80 hover:text-white' : 'text-fg/80 hover:text-ink' }}">{{ $item['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            @if (setting('footer.show_newsletter', true) && setting('newsletter.enabled', true))
                <div class="lg:col-span-3">
                    <h2 class="text-xs font-semibold tracking-[.14em] uppercase font-sans {{ $dark ? 'text-white/50' : 'text-muted' }}">Weekly case</h2>
                    <p class="mt-4 text-[0.9375rem] {{ $dark ? 'text-white/80' : 'text-fg/80' }}">{{ setting('newsletter.signup_heading') }}</p>
                    <livewire:newsletter-form variant="footer" :dark="$dark" source="footer" />
                </div>
            @endif
        </div>

        <div class="mt-14 border-t pt-8 {{ $dark ? 'border-white/10' : 'border-line' }}">
            @if (setting('site.medical_disclaimer'))
                <p class="max-w-4xl text-xs leading-relaxed {{ $dark ? 'text-white/45' : 'text-muted' }}">{{ setting('site.medical_disclaimer') }}</p>
            @endif
            <div class="mt-5 flex flex-col gap-4 text-sm sm:flex-row sm:items-center sm:justify-between {{ $dark ? 'text-white/55' : 'text-muted' }}">
                <p>{{ $copyright }}</p>
                @if ($legal)
                    <ul class="flex flex-wrap gap-x-6 gap-y-2" role="list">
                        @foreach ($legal as $item)
                            <li><a href="{{ $item['url'] }}" class="transition {{ $dark ? 'hover:text-white' : 'hover:text-ink' }}">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</footer>
