@php
    $trackable = setting('analytics.enabled', true) && ! (setting('analytics.exclude_staff', true) && auth()->user()?->isStaff()) && ! ($noTracking ?? false);
@endphp
@if ($trackable)
<script>
    window.__oa = { endpoint: @js(route('analytics.collect')), event: @js(route('analytics.event')), subject: @js($trackSubject ?? null) };
</script>
@endif
