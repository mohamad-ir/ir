<x-filament-panels::page>
    @if (! $zip)
        <div class="rounded-xl bg-danger-50 p-4 text-sm text-danger-800">The PHP <code>zip</code> extension is not enabled on this server. Enable it in DirectAdmin › Select PHP version › Extensions to use backups.</div>
    @endif
    <div class="rounded-xl bg-white p-5 text-sm shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p><strong>Automatic backups:</strong> {{ ['off' => 'off', 'daily' => 'every day', 'weekly' => 'every week'][$schedule] ?? $schedule }}. Backups are stored privately on the server — download copies regularly and keep them somewhere safe (they contain the whole database).</p>
    </div>
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-white/5"><tr><th class="px-5 py-3">Backup</th><th class="px-5 py-3">Created</th><th class="px-5 py-3 text-right">Size</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($backups as $b)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs">{{ $b['name'] }}</td>
                        <td class="px-5 py-3">{{ \Illuminate\Support\Carbon::createFromTimestamp($b['date'])->timezone(config('app.display_timezone', config('app.timezone')))->format('j M Y H:i') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ \Illuminate\Support\Number::fileSize($b['size'], 1) }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <x-filament::button size="xs" color="gray" icon="heroicon-m-arrow-down-tray" wire:click="download('{{ $b['name'] }}')">Download</x-filament::button>
                                {{ ($this->restoreAction)(['name' => $b['name']]) }}
                                <x-filament::button size="xs" color="gray" icon="heroicon-m-trash" wire:click="deleteBackup('{{ $b['name'] }}')" wire:confirm="Delete this backup permanently?"></x-filament::button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">No backups yet. Create your first one now.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
