@if (($data['plans'] ?? collect())->isNotEmpty())
    <x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" align="center" />
    @include('membership.plans', ['plans' => $data['plans'], 'subscription' => $data['subscription'] ?? null, 'paymentsEnabled' => $data['payments_enabled'] ?? false])
@elseif ($context['preview'] ?? false)
    <p class="rounded-ui bg-subtle p-6 text-center text-sm text-muted">Membership plans — add a plan under Sales › Membership plans.</p>
@endif
