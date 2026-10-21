@php
    $labels = fn ($k) => \Illuminate\Support\Str::headline(str_replace('_id', '', $k));
    $text = function ($v) {
        if (is_array($v)) { $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
        return \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $v))), 400) ?: '—';
    };
    $list = $revisions->values();
@endphp
<div class="space-y-3">
    @forelse ($list as $i => $rev)
        @php
            $prev = $list[$i + 1]->data ?? [];
            $changed = collect($rev->data)->filter(fn ($v, $k) => json_encode($v) !== json_encode($prev[$k] ?? null))->keys()->reject(fn ($k) => in_array($k, ['updated_at', 'created_at']));
            $diffCurrent = collect($rev->data)->filter(fn ($v, $k) => json_encode($v) !== json_encode($current[$k] ?? null))->keys()->reject(fn ($k) => in_array($k, ['updated_at', 'created_at']));
        @endphp
        <div x-data="{ open: false }" class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3 p-3">
                <span @class(['flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold', 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300' => $i === 0, 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' => $i > 0])>
                    {{ $i === 0 ? 'Now' : '#'.($list->count() - $i) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ ucfirst($rev->event) }} by {{ $rev->user?->name ?? 'System' }}</p>
                    <p class="text-xs text-gray-500">{{ $rev->created_at?->format('j M Y, H:i') }} · {{ $rev->created_at?->diffForHumans() }}
                        @if ($changed->isNotEmpty() && $rev->event !== 'created') · changed {{ $changed->take(4)->map($labels)->implode(', ') }}{{ $changed->count() > 4 ? '…' : '' }} @endif</p>
                </div>
                <x-filament::link tag="button" size="sm" x-on:click="open = !open">Compare</x-filament::link>
                @if ($i > 0)
                    <x-filament::button size="xs" color="gray" wire:click="restoreRevision({{ $rev->id }})" wire:confirm="Restore this version? Your current version will be kept in the history.">Restore</x-filament::button>
                @endif
            </div>
            <div x-show="open" x-collapse class="border-t border-gray-100 p-3 text-xs dark:border-white/10">
                @if ($diffCurrent->isEmpty())
                    <p class="text-gray-500">Identical to the current version.</p>
                @else
                    <p class="mb-2 font-semibold text-gray-600 dark:text-gray-300">Differences from the current version</p>
                    <dl class="space-y-3">
                        @foreach ($diffCurrent as $key)
                            <div>
                                <dt class="font-semibold text-gray-700 dark:text-gray-200">{{ $labels($key) }}</dt>
                                <dd class="mt-1 grid gap-2 sm:grid-cols-2">
                                    <div class="rounded-lg bg-danger-50 p-2 text-danger-900 dark:bg-danger-500/10 dark:text-danger-200"><span class="block text-[10px] font-bold uppercase opacity-60">This revision</span>{{ $text($rev->data[$key] ?? null) }}</div>
                                    <div class="rounded-lg bg-success-50 p-2 text-success-900 dark:bg-success-500/10 dark:text-success-200"><span class="block text-[10px] font-bold uppercase opacity-60">Current</span>{{ $text($current[$key] ?? null) }}</div>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">No revisions yet — they appear after the first save.</p>
    @endforelse
</div>
