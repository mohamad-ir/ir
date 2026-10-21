<x-filament-widgets::widget>
    <x-filament::section heading="Recent activity" icon="heroicon-o-clock">
        <x-slot name="afterHeader"><x-filament::link :href="\App\Filament\Resources\ActivityLogs\ActivityLogResource::getUrl()" size="sm">Full log</x-filament::link></x-slot>
        <ul class="-my-2 divide-y divide-gray-100 dark:divide-white/5">
            @forelse ($logs as $log)
                <li class="py-2.5 text-sm"><span class="font-medium">{{ $log->user?->name ?? 'System' }}</span> <span class="text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::lcfirst($log->description) }}</span><div class="text-xs text-gray-400">{{ $log->created_at?->diffForHumans() }}</div></li>
            @empty
                <li class="py-6 text-center text-sm text-gray-500">No activity yet.</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
