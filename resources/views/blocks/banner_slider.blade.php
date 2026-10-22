@if (! empty($data['banner']))
    <x-banner-slider :banner="$data['banner']" :contained="false" />
@else
    <p class="rounded-ui bg-subtle p-6 text-center text-sm text-muted">Banner slider — choose an active banner with at least one live slide.</p>
@endif
