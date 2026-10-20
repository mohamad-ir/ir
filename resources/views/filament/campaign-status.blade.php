@php($pct = $campaign->recipients_count ? round(($campaign->sent_count + $campaign->failed_count) / $campaign->recipients_count * 100) : 0)
<div @if ($campaign->status === \App\Enums\CampaignStatus::Sending) wire:poll.5s="processBatch" @endif class="space-y-3 text-sm">
    <div class="flex items-center justify-between"><span class="text-gray-500">Status</span><x-filament::badge :color="$campaign->status->getColor()">{{ $campaign->status->getLabel() }}</x-filament::badge></div>
    @if ($campaign->scheduled_at && $campaign->status === \App\Enums\CampaignStatus::Scheduled)
        <div class="flex justify-between"><span class="text-gray-500">Scheduled</span><span>{{ $campaign->scheduled_at->format('j M Y H:i') }}</span></div>
    @endif
    @if ($campaign->recipients_count)
        <div>
            <div class="mb-1 flex justify-between"><span class="text-gray-500">Progress</span><span>{{ number_format($campaign->sent_count) }} / {{ number_format($campaign->recipients_count) }}</span></div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"><div class="h-full rounded-full bg-primary-600 transition-all" style="width: {{ $pct }}%"></div></div>
        </div>
        <div class="grid grid-cols-3 gap-2 pt-2 text-center">
            <div class="rounded-lg bg-gray-50 p-2 dark:bg-white/5"><div class="text-lg font-semibold">{{ $campaign->openRate() ?? 0 }}%</div><div class="text-xs text-gray-500">Opened</div></div>
            <div class="rounded-lg bg-gray-50 p-2 dark:bg-white/5"><div class="text-lg font-semibold">{{ $campaign->clickRate() ?? 0 }}%</div><div class="text-xs text-gray-500">Clicked</div></div>
            <div class="rounded-lg bg-gray-50 p-2 dark:bg-white/5"><div class="text-lg font-semibold">{{ number_format($campaign->failed_count) }}</div><div class="text-xs text-gray-500">Failed</div></div>
        </div>
        <p class="text-xs text-gray-500">Open tracking depends on the recipient loading images and is an estimate.</p>
    @endif
</div>
