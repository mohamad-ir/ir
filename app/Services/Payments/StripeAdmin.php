<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\ActivityLog;

/** Dashboard actions that change things in Stripe: refunds and subscription cancellation. */
class StripeAdmin
{
    public function __construct(protected StripeClient $stripe, protected Fulfillment $fulfillment) {}

    /** @param int|null $amount minor units; null = refund the remaining balance */
    public function refund(Order $order, ?int $amount = null, ?string $reason = null): void
    {
        if (! $order->stripe_payment_intent_id) {
            throw new StripeException('This order has no Stripe payment to refund. Refund it from the Stripe dashboard instead.');
        }

        $remaining = $order->total - $order->refunded_amount;
        $amount = min($amount ?? $remaining, $remaining);
        if ($amount <= 0) {
            throw new StripeException('Nothing left to refund on this order.');
        }

        $this->stripe->post('refunds', array_filter([
            'payment_intent' => $order->stripe_payment_intent_id,
            'amount' => $amount,
            'reason' => $reason,
            'metadata' => ['order_number' => $order->number, 'by' => auth()->user()?->email],
        ]), 'refund-'.$order->id.'-'.($order->refunded_amount + $amount));

        $this->fulfillment->applyRefund($order, $order->refunded_amount + $amount);
        ActivityLog::record('refund', 'Refunded '.\App\Support\Money::format($amount, $order->currency).' on order '.$order->number, $order);
    }

    public function cancelSubscription(Subscription $subscription, bool $immediately = false): void
    {
        $data = $immediately
            ? $this->stripe->delete('subscriptions/'.$subscription->stripe_subscription_id)
            : $this->stripe->post('subscriptions/'.$subscription->stripe_subscription_id, ['cancel_at_period_end' => true]);

        $this->fulfillment->syncSubscription($data);
        ActivityLog::record('subscription_cancelled', 'Cancelled membership of '.$subscription->user?->email.($immediately ? ' immediately' : ' at period end'), $subscription);
    }

    public function resumeSubscription(Subscription $subscription): void
    {
        $data = $this->stripe->post('subscriptions/'.$subscription->stripe_subscription_id, ['cancel_at_period_end' => false]);
        $this->fulfillment->syncSubscription($data);
    }
}
