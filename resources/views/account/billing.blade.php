@extends('account.layout')
@section('account')
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section>
            <h2 class="font-heading text-2xl font-semibold text-ink">Membership</h2>
            <div class="card mt-5 p-6">
                @if ($subscription && $isMember)
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="font-heading text-xl font-semibold text-ink">{{ $subscription->plan?->name ?? 'Membership' }}</p>
                            <p class="mt-1 text-muted">{{ $subscription->priceLabel() }}</p>
                        </div>
                        <span class="chip chip-accent">{{ $subscription->status->getLabel() }}</span>
                    </div>
                    <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                        @if ($subscription->trial_ends_at && $subscription->trial_ends_at->isFuture())
                            <div><dt class="text-muted">Free trial ends</dt><dd class="font-medium text-ink">{{ $subscription->trial_ends_at->format('j F Y') }}</dd></div>
                        @endif
                        @if ($subscription->current_period_end)
                            <div><dt class="text-muted">{{ $subscription->cancel_at_period_end ? 'Access ends on' : 'Renews on' }}</dt><dd class="font-medium text-ink">{{ $subscription->current_period_end->format('j F Y') }}</dd></div>
                        @endif
                    </dl>
                    @if ($subscription->status->value === 'past_due')
                        <p class="mt-5 rounded-ui bg-highlight/10 px-4 py-3 text-sm ring-1 ring-highlight/25">Your last payment did not go through. Please update your payment method to keep your membership.</p>
                    @endif
                    @if ($subscription->cancel_at_period_end)
                        <p class="mt-5 text-sm text-muted">Your membership is cancelled and will not renew. You can resume it in the billing portal before it ends.</p>
                    @endif
                @else
                    <p class="text-muted">You do not have an active membership.</p>
                    <a href="{{ route('membership') }}" class="btn btn-primary mt-5">See membership options</a>
                @endif
                @if ($user->stripe_customer_id && $paymentsEnabled)
                    <form method="post" action="{{ route('account.billing.portal') }}" class="mt-6 border-t border-line pt-5">@csrf
                        <button class="btn btn-secondary"><x-heroicon-m-credit-card /> Manage payment method, invoices & cancellation</button>
                        <p class="mt-2 text-xs text-muted">Opens our secure billing portal provided by Stripe.</p>
                    </form>
                @endif
            </div>

            <h2 class="mt-12 font-heading text-2xl font-semibold text-ink">Payment history</h2>
            @if ($orders->isEmpty())
                <p class="mt-4 text-muted">No payments yet.</p>
            @else
                <div class="mt-5 overflow-x-auto rounded-card bg-surface ring-1 ring-line">
                    <table class="w-full min-w-[34rem] text-left text-sm">
                        <thead class="border-b border-line text-xs uppercase tracking-wider text-muted"><tr><th class="px-5 py-3 font-semibold">Date</th><th class="px-5 py-3 font-semibold">Item</th><th class="px-5 py-3 text-right font-semibold">Amount</th><th class="px-5 py-3"></th></tr></thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-muted">{{ ($order->paid_at ?? $order->created_at)->format('j M Y') }}</td>
                                    <td class="px-5 py-3"><span class="text-ink">{{ $order->description }}</span><span class="block text-xs text-muted">{{ $order->number }}@if($order->status->value !== 'paid') · {{ $order->status->getLabel() }}@endif</span></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-ink">{{ $order->totalLabel() }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        @if ($order->invoice_pdf_url)<a href="{{ $order->invoice_pdf_url }}" class="link" target="_blank" rel="noopener">Invoice</a>
                                        @elseif ($order->receipt_url)<a href="{{ $order->receipt_url }}" class="link" target="_blank" rel="noopener">Receipt</a>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        <aside class="space-y-5 text-sm text-muted">
            <div class="rounded-card bg-subtle p-5 ring-1 ring-line">
                <p class="font-semibold text-ink">Secure payments</p>
                <p class="mt-1">Payments are processed by Stripe. Your card details are never stored on this website.</p>
            </div>
            <div class="rounded-card bg-subtle p-5 ring-1 ring-line">
                <p class="font-semibold text-ink">Need help?</p>
                <p class="mt-1">For billing questions or a VAT invoice in a different name, <a href="{{ route('contact') }}" class="link">contact us</a>. See our <a href="{{ url(setting('payments.refund_url', '/refund-policy')) }}" class="link">refund policy</a>.</p>
            </div>
        </aside>
    </div>
@endsection
