@php($total = count($items))
<x-filament-widgets::widget>
    @if ($done < $total)
        <div x-data="{ open: true }" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-4 p-5">
                <div class="relative size-12 shrink-0">
                    <svg viewBox="0 0 36 36" class="size-12 -rotate-90"><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-100 dark:text-white/10"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-primary-600" stroke-dasharray="{{ round($done / $total * 97.4, 1) }} 97.4"/></svg>
                    <span class="absolute inset-0 grid place-items-center text-xs font-bold">{{ $done }}/{{ $total }}</span>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold">Welcome, {{ auth()->user()->name }} — let’s get your website ready</h2>
                    <p class="text-sm text-gray-500">A few steps remain before launch.</p>
                </div>
                <x-filament::icon-button icon="heroicon-m-chevron-down" color="gray" x-on:click="open = !open" ::class="open && 'rotate-180'" label="Toggle" />
            </div>
            <ul x-show="open" x-collapse class="grid gap-px border-t border-gray-100 bg-gray-100 sm:grid-cols-2 dark:border-white/5 dark:bg-white/5">
                @foreach ($items as [$label, $ok, $url])
                    <li class="bg-white dark:bg-gray-900">
                        <a href="{{ $url }}" class="flex items-center gap-3 px-5 py-3 text-sm transition hover:bg-gray-50 dark:hover:bg-white/5">
                            @if ($ok)
                                <x-heroicon-s-check-circle class="size-5 shrink-0 text-success-500" /><span class="text-gray-500 line-through">{{ $label }}</span>
                            @else
                                <span class="size-5 shrink-0 rounded-full border-2 border-gray-300 dark:border-white/20"></span><span class="font-medium">{{ $label }}</span>
                                <x-heroicon-m-arrow-right class="ml-auto size-4 text-gray-400" />
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-widgets::widget>
