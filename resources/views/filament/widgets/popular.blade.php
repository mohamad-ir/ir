<x-filament-widgets::widget>
    <x-filament::section heading="Popular this month" icon="heroicon-o-fire">
        <x-slot name="afterHeader"><x-filament::link :href="\App\Filament\Pages\Insights\Analytics::getUrl()" size="sm">Analytics</x-filament::link></x-slot>
        <ol class="-my-2 divide-y divide-gray-100 dark:divide-white/5">
            @forelse ($pages as $i => $p)
                <li class="flex items-center gap-3 py-2.5 text-sm"><span class="w-5 text-right font-semibold text-gray-400">{{ $i + 1 }}</span><a href="{{ url($p->path) }}" target="_blank" class="min-w-0 flex-1 truncate font-medium hover:text-primary-600">{{ $p->title }}</a><span class="tabular-nums text-gray-500">{{ number_format($p->views) }}</span></li>
            @empty
                <li class="py-6 text-center text-sm text-gray-500">Views appear here once visitors arrive.</li>
            @endforelse
        </ol>
    </x-filament::section>
</x-filament-widgets::widget>
