<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Mail\MembershipStarted;
use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Webinar;
use App\Services\Learning\LearningService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns Stripe events into access. Every method is idempotent: the same event
 * may arrive more than once (webhook retries) and the success page also calls
 * fulfilCheckoutSession() so access is granted even if webhooks are delayed.
 *
 * Handles both the classic and the 2025 ("basil") Stripe object shapes, because
 * webhook payloads use the API version configured on the Stripe account.
 */
class Fulfillment
{
    /** Events the webhook endpoint subscribes to. */
    public const EVENTS = [
        'checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed', 'checkout.session.expired',
        'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted', 'customer.subscription.paused', 'customer.subscription.resumed',
        'invoice.paid', 'charge.refunded',
    ];

    public function __construct(protected StripeClient $stripe, protected LearningService $learning) {}

    public function handle(array $event): void
    {
        $object = $event['data']['object'] ?? [];

        match ($event['type'] ?? null) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->fulfilCheckoutSession($object),
            'checkout.session.async_payment_failed' => $this->failCheckoutSession($object, OrderStatus::Failed),
            'checkout.session.expired' => $this->failCheckoutSession($object, OrderStatus::Canceled),
            'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted',
            'customer.subscription.paused', 'customer.subscription.resumed' => $this->syncSubscription($object),
            'invoice.paid' => $this->recordInvoice($object),
            'charge.refunded' => $this->recordRefund($object),
            default => null,
        };
    }

    // Checkout ---------------------------------------------------------------

    public function fulfilCheckoutSession(array $session): void
    {
        $metadata = $session['metadata'] ?? [];
        $type = $metadata['type'] ?? null;

        if ($type === 'membership') {
            $subscriptionId = self::id($session['subscription'] ?? null);
            if ($subscriptionId) {
                $subscription = is_array($session['subscription'] ?? null) ? $session['subscription'] : $this->stripe->get('subscriptions/'.$subscriptionId);
                $this->syncSubscription($subscription, $metadata);
            }

            return;
        }

        if (! in_array($type, ['course', 'webinar'], true)) {
            return;
        }

        // Asynchronous methods (e.g. SEPA Direct Debit) complete the session before the money arrives.
        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        DB::transaction(function () use ($session, $metadata, $type) {
            $order = Order::lockForUpdate()->find($metadata['order_id'] ?? 0)
                ?? Order::lockForUpdate()->where('stripe_checkout_session_id', $session['id'])->first();
            if (! $order || $order->isPaid() || in_array($order->status, [OrderStatus::Refunded], true)) {
                return;
            }

            $details = $session['total_details'] ?? [];
            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'stripe_checkout_session_id' => $session['id'],
                'stripe_payment_intent_id' => self::id($session['payment_intent'] ?? null),
                'stripe_invoice_id' => self::id($session['invoice'] ?? null),
                'total' => $session['amount_total'] ?? $order->total,
                'subtotal' => $session['amount_subtotal'] ?? $order->subtotal,
                'tax' => $details['amount_tax'] ?? 0,
                'currency' => strtoupper($session['currency'] ?? $order->currency),
                'meta' => array_filter([
                    ...($order->meta ?? []),
                    'discount' => $details['amount_discount'] ?? null,
                    'customer_country' => $session['customer_details']['address']['country'] ?? null,
                    'tax_ids' => collect($session['customer_details']['tax_ids'] ?? [])->pluck('value')->all() ?: null,
                ]),
            ]);

            $user = $order->user ?? User::find($metadata['user_id'] ?? 0);
            if (! $user) {
                Log::warning("Paid order {$order->number} has no user to grant access to.");

                return;
            }

            if ($type === 'course' && ($course = Course::withTrashed()->find($order->purchasable_id))) {
                $this->learning->enroll($user, $course, 'purchase', $order);
            }
            if ($type === 'webinar' && ($webinar = Webinar::withTrashed()->find($order->purchasable_id))) {
                $this->learning->register($user, $webinar, 'purchase', $order);
            }
        });

        $this->attachReceipt($metadata['order_id'] ?? null);
    }

    protected function failCheckoutSession(array $session, OrderStatus $status): void
    {
        Order::where('stripe_checkout_session_id', $session['id'] ?? '')->where('status', OrderStatus::Pending)->update(['status' => $status]);
    }

    /** Adds Stripe's hosted receipt link to the order (best effort). */
    protected function attachReceipt(int|string|null $orderId): void
    {
        $order = $orderId ? Order::find($orderId) : null;
        if (! $order || $order->receipt_url || ! $order->stripe_payment_intent_id) {
            return;
        }

        try {
            $intent = $this->stripe->get('payment_intents/'.$order->stripe_payment_intent_id, ['expand' => ['latest_charge']]);
            $order->update(['receipt_url' => $intent['latest_charge']['receipt_url'] ?? null]);
        } catch (StripeException $e) {
            Log::info('Receipt link unavailable for '.$order->number.': '.$e->getMessage());
        }
    }

    // Subscriptions -----------------------------------------------------------

    public function syncSubscription(array $data, array $sessionMetadata = []): ?Subscription
    {
        $metadata = ($data['metadata'] ?? []) + $sessionMetadata;
        $customerId = self::id($data['customer'] ?? null);

        $user = User::find($metadata['user_id'] ?? 0)
            ?? ($customerId ? User::where('stripe_customer_id', $customerId)->first() : null);
        if (! $user) {
            Log::warning('Stripe subscription '.($data['id'] ?? '?').' does not match a user.');

            return null;
        }

        $item = $data['items']['data'][0] ?? [];
        $price = $item['price'] ?? [];
        $plan = MembershipPlan::find($metadata['plan_id'] ?? 0)
            ?? (isset($price['id']) ? MembershipPlan::where('stripe_price_id', $price['id'])->first() : null);

        $status = SubscriptionStatus::tryFrom($data['status'] ?? '') ?? SubscriptionStatus::Incomplete;

        return DB::transaction(function () use ($data, $user, $plan, $status, $item, $price, $customerId) {
            $subscription = Subscription::lockForUpdate()->firstOrNew(['stripe_subscription_id' => $data['id']]);
            $wasGranting = $subscription->exists && $subscription->grantsAccess();
            $neverGranted = ! $subscription->exists || in_array($subscription->status, [SubscriptionStatus::Incomplete, null], true);

            $subscription->fill([
                'user_id' => $user->id,
                'membership_plan_id' => $plan?->id ?? $subscription->membership_plan_id,
                'stripe_customer_id' => $customerId,
                'status' => $status,
                'amount' => $price['unit_amount'] ?? $subscription->amount,
                'currency' => isset($price['currency']) ? strtoupper($price['currency']) : $subscription->currency,
                'interval' => $price['recurring']['interval'] ?? $subscription->interval,
                'current_period_end' => self::time($data['current_period_end'] ?? $item['current_period_end'] ?? null),
                'trial_ends_at' => self::time($data['trial_end'] ?? null),
                'cancel_at_period_end' => (bool) ($data['cancel_at_period_end'] ?? false) || filled($data['cancel_at'] ?? null),
                'canceled_at' => self::time($data['canceled_at'] ?? null),
                'ended_at' => self::time($data['ended_at'] ?? null),
            ])->save();

            if (! $user->stripe_customer_id && $customerId) {
                $user->forceFill(['stripe_customer_id' => $customerId])->saveQuietly();
            }
            $user->forgetMembership();

            if (! $wasGranting && $neverGranted && $subscription->grantsAccess()) {
                $this->learning->mail($user, new MembershipStarted($subscription));
            }

            return $subscription;
        });
    }

    /** Each paid membership invoice becomes an order, so revenue reports include renewals. */
    public function recordInvoice(array $invoice): void
    {
        $subscriptionId = self::id($invoice['subscription'] ?? $invoice['parent']['subscription_details']['subscription'] ?? null);
        if (! $subscriptionId || ! isset($invoice['id']) || (int) ($invoice['amount_paid'] ?? 0) === 0) {
            return; // one-time purchases are recorded from Checkout; €0 trial invoices are skipped
        }

        $customerId = self::id($invoice['customer'] ?? null);
        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();
        $user = $subscription?->user ?? ($customerId ? User::where('stripe_customer_id', $customerId)->first() : null);
        $plan = $subscription?->plan;

        $tax = (int) ($invoice['tax'] ?? collect($invoice['total_taxes'] ?? [])->sum('amount'));

        Order::firstOrCreate(['stripe_invoice_id' => $invoice['id']], [
            'user_id' => $user?->id,
            'email' => $invoice['customer_email'] ?? $user?->email ?? 'unknown',
            'name' => $invoice['customer_name'] ?? $user?->name,
            'type' => 'membership',
            'purchasable_type' => $plan ? $plan->getMorphClass() : null,
            'purchasable_id' => $plan?->id,
            'description' => ($plan?->name ?? 'Membership').' — '.(($invoice['billing_reason'] ?? '') === 'subscription_create' ? 'first payment' : 'renewal'),
            'subtotal' => (int) ($invoice['subtotal'] ?? $invoice['amount_paid']),
            'tax' => $tax,
            'total' => (int) $invoice['amount_paid'],
            'currency' => strtoupper($invoice['currency'] ?? 'EUR'),
            'status' => OrderStatus::Paid,
            'stripe_subscription_id' => $subscriptionId,
            'stripe_payment_intent_id' => self::id($invoice['payment_intent'] ?? null),
            'receipt_url' => $invoice['hosted_invoice_url'] ?? null,
            'invoice_pdf_url' => $invoice['invoice_pdf'] ?? null,
            'paid_at' => self::time($invoice['status_transitions']['paid_at'] ?? null) ?? now(),
        ]);
    }

    // Refunds -----------------------------------------------------------------

    public function recordRefund(array $charge): void
    {
        $intentId = self::id($charge['payment_intent'] ?? null);
        $order = $intentId ? Order::where('stripe_payment_intent_id', $intentId)->first() : null;
        $order ??= isset($charge['invoice']) ? Order::where('stripe_invoice_id', self::id($charge['invoice']))->first() : null;
        if (! $order) {
            return;
        }

        $this->applyRefund($order, (int) ($charge['amount_refunded'] ?? 0));
    }

    public function applyRefund(Order $order, int $refundedTotal): void
    {
        $full = $refundedTotal >= $order->total;
        $order->update([
            'refunded_amount' => min($refundedTotal, $order->total),
            'status' => $full ? OrderStatus::Refunded : OrderStatus::PartiallyRefunded,
            'refunded_at' => now(),
        ]);

        // A full refund of a course or ticket removes the access it bought.
        if ($full) {
            $this->learning->revokeEnrollment($order);
            $this->learning->revokeRegistration($order);
        }
    }

    // Helpers -----------------------------------------------------------------

    /** Stripe fields may be an ID string or an expanded object. */
    public static function id(mixed $value): ?string
    {
        return is_array($value) ? ($value['id'] ?? null) : (is_string($value) && $value !== '' ? $value : null);
    }

    public static function time(mixed $timestamp): ?Carbon
    {
        return is_numeric($timestamp) && $timestamp > 0 ? Carbon::createFromTimestamp((int) $timestamp) : null;
    }
}
