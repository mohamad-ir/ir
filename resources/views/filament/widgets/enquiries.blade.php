<x-filament-widgets::widget>
    <x-filament::section heading="Latest enquiries" icon="heroicon-o-inbox">
        <x-slot name="afterHeader"><x-filament::link :href="$all" size="sm">View all</x-filament::link></x-slot>
        <ul class="-my-2 divide-y divide-gray-100 dark:divide-white/5">
            @forelse ($enquiries as $e)
                <li><a href="{{ $url($e) }}" class="block py-2.5 text-sm hover:text-primary-600">
                    <span class="flex items-center gap-2">@if($e->status->value === 'new')<span class="size-2 rounded-full bg-danger-500"></span>@endif<span class="truncate font-medium">{{ $e->subject }}</span></span>
                    <span class="text-xs text-gray-500">{{ $e->name }} · {{ $e->type->getLabel() }} · {{ $e->created_at->diffForHumans() }}</span>
                </a></li>
            @empty
                <li class="py-6 text-center text-sm text-gray-500">No enquiries yet.</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
