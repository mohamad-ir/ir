<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-m-check" wire:loading.attr="disabled">{{ $this->getFormActionsLabel() }}</x-filament::button>
            <span wire:loading wire:target="save" class="text-sm text-gray-500">Saving…</span>
        </div>
    </form>
</x-filament-panels::page>
