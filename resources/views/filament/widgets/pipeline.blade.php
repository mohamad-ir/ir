<x-filament-widgets::widget>
    <x-filament::section heading="Editorial pipeline" description="Drafts, items in review and scheduled publications" icon="heroicon-o-queue-list">
        <ul class="-my-2 divide-y divide-gray-100 dark:divide-white/5">
            @forelse ($items as $i)
                <li><a href="{{ $i['url'] }}" class="flex items-center gap-3 py-2.5 text-sm hover:text-primary-600">
                    <x-filament::badge :color="$i['color']" size="sm">{{ $i['status'] }}</x-filament::badge>
                    <span class="min-w-0 flex-1"><span class="block truncate font-medium">{{ $i['title'] }}</span><span class="text-xs text-gray-500">{{ $i['label'] }} · {{ $i['status'] === 'Scheduled' ? 'publishes '.$i['when']->format('j M H:i') : 'edited '.$i['when']->diffForHumans() }}</span></span>
                </a></li>
            @empty
                <li class="py-6 text-center text-sm text-gray-500">Nothing in progress — everything is published.</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
