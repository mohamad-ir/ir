{{-- Popups, slide-ins and bottom bars for this page. Rules: App\Services\Popups\PopupService + resources/js/popups.js --}}
@if (($popupItems ?? collect())->isNotEmpty())
    <script>window.__popups = {{ \Illuminate\Support\Js::from(app(\App\Services\Popups\PopupService::class)->globalConfig()) }};</script>
    @foreach ($popupItems->reject(fn ($p) => $p->type === 'bar' && $p->position === 'top') as $popup)
        @include('popups.item', ['popup' => $popup])
    @endforeach
@endif
