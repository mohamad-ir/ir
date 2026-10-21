<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\User;
use App\Models\Webinar;
use App\Support\Currency;
use App\Support\Settings;

/**
 * Builds Stripe Checkout sessions. Stripe hosts the payment page, so card
 * details never touch this server (PCI SAQ-A) and Apple Pay, Google Pay,
 * SEPA, iDEAL, Bancontact etc. appear automatically where enabled.
 */
class Checkout
{
    public function __construct(protected StripeClient $stripe) {}

    /** Returns the Stripe-hosted URL to redirect the member to. */
    public function membership(User $user, MembershipPlan $plan): string
    {
        [, $currency] = $plan->priceFor(Currency::current());
        $priceId = $this->ensurePlanPrice($plan, $currency);

        $session = $this->stripe->post('checkout/sessions', [
            'mode' => 'subscription',
            'customer' => $this->ensureCustomer($user),
            'client_reference_id' => (string) $user->id,
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'subscription_data' => array_filter([
                'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id],
                'trial_period_days' => $plan->trial_days ?: null,
                'description' => $plan->name,
            ]),
            'metadata' => ['type' => 'membership', 'user_id' => $user->id, 'plan_id' => $plan->id],
            ...$this->commonOptions(),
        ]);

        return $session['url'];
    }

    public function course(User $user, Course $course, bool $consent): string
    {
        return $this->oneTime($user, $course, 'course', $course->title, $consent);
    }

    public function webinar(User $user, Webinar $webinar, bool $consent): string
    {
        return $this->oneTime($user, $webinar, 'webinar', 'Webinar: '.$webinar->title.' ('.$webinar->displayStart('j M Y').')', $consent);
    }

    protected function oneTime(User $user, Course|Webinar $item, string $type, string $description, bool $consent): string
    {
        [$amount, $currency] = $item->priceFor(Currency::current());

        $order = Order::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'type' => $type,
            'purchasable_type' => $item->getMorphClass(),
            'purchasable_id' => $item->id,
            'description' => $description,
            'subtotal' => $amount,
            'total' => $amount,
            'currency' => $currency,
            'status' => OrderStatus::Pending,
            'consent_at' => $consent ? now() : null,
            'meta' => ['ip' => request()?->ip()],
        ]);

        $metadata = ['type' => $type, 'order_id' => $order->id, 'order_number' => $order->number, 'user_id' => $user->id, 'item_id' => $item->id];

        $session = $this->stripe->post('checkout/sessions', [
            'mode' => 'payment',
            'customer' => $this->ensureCustomer($user),
            'client_reference_id' => (string) $user->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($currency),
                    'unit_amount' => $amount,
                    'product' => $this->ensureProduct($item),
                    'tax_behavior' => $this->taxBehavior(),
                ],
            ]],
            'payment_intent_data' => ['metadata' => $metadata, 'description' => $order->number.' — '.$description],
            'invoice_creation' => Settings::get('payments.invoice_one_time') ? ['enabled' => true, 'invoice_data' => ['metadata' => $metadata]] : null,
            'metadata' => $metadata,
            ...$this->commonOptions(),
        ], 'order-'.$order->id);

        $order->update(['stripe_checkout_session_id' => $session['id']]);

        return $session['url'];
    }

    /** Opens Stripe's Customer Portal: update card, download invoices, cancel. */
    public function portal(User $user): string
    {
        abort_unless($user->stripe_customer_id, 404);

        return $this->stripe->post('billing_portal/sessions', [
            'customer' => $user->stripe_customer_id,
            'return_url' => route('account.billing'),
        ])['url'];
    }

    protected function commonOptions(): array
    {
        $automaticTax = (bool) Settings::get('payments.automatic_tax');
        $terms = Settings::get('payments.terms_url');

        return array_filter([
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel'),
            'allow_promotion_codes' => (bool) Settings::get('payments.allow_promotion_codes') ?: null,
            'automatic_tax' => $automaticTax ? ['enabled' => true] : null,
            'tax_id_collection' => Settings::get('payments.collect_tax_id') ? ['enabled' => true] : null,
            'billing_address_collection' => $automaticTax || Settings::get('payments.collect_tax_id') ? 'required' : 'auto',
            'customer_update' => ['address' => 'auto', 'name' => 'auto'],
            'custom_text' => $terms ? ['submit' => ['message' => 'By paying you agree to our terms of sale: '.url($terms)]] : null,
            'locale' => 'auto',
        ], fn ($v) => $v !== null);
    }

    protected function taxBehavior(): string
    {
        return Settings::get('payments.prices_include_tax', true) ? 'inclusive' : 'exclusive';
    }

    public function ensureCustomer(User $user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customer = $this->stripe->post('customers', [
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => $user->id],
        ], 'customer-user-'.$user->id);

        $user->forceFill(['stripe_customer_id' => $customer['id']])->saveQuietly();

        return $customer['id'];
    }

    protected function ensureProduct(Course|Webinar $item): string
    {
        if ($item->stripe_product_id) {
            return $item->stripe_product_id;
        }

        $product = $this->stripe->post('products', [
            'name' => $item instanceof Webinar ? 'Webinar: '.$item->title : $item->title,
            'metadata' => ['type' => $item->getMorphClass(), 'id' => $item->id],
            'url' => $item->url(),
        ], $item->getMorphClass().'-product-'.$item->id);

        $item->forceFill(['stripe_product_id' => $product['id']])->saveQuietly();

        return $product['id'];
    }

    /** Stripe prices are immutable: a new Price is created whenever the amount, interval or tax mode changes for that currency. */
    public function ensurePlanPrice(MembershipPlan $plan, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? $plan->currency);
        $amount = $plan->priceIn($currency) ?? throw new StripeException("This plan has no price in {$currency}.");
        $fingerprint = $plan->priceFingerprint($currency).':'.$this->taxBehavior();
        $known = $plan->stripe_prices[$currency] ?? null;

        if ($known && ($known['fingerprint'] ?? null) === $fingerprint) {
            return $known['id'];
        }

        if (! $plan->stripe_product_id) {
            $product = $this->stripe->post('products', [
                'name' => $plan->name.' membership',
                'metadata' => ['type' => 'membership_plan', 'id' => $plan->id],
            ], 'plan-product-'.$plan->id);
            $plan->stripe_product_id = $product['id'];
        }

        $price = $this->stripe->post('prices', [
            'product' => $plan->stripe_product_id,
            'currency' => strtolower($currency),
            'unit_amount' => $amount,
            'recurring' => ['interval' => $plan->interval],
            'tax_behavior' => $this->taxBehavior(),
            'metadata' => ['plan_id' => $plan->id],
        ], 'plan-price-'.$plan->id.'-'.md5($fingerprint));

        if ($known && $known['id'] !== $price['id']) {
            try {
                $this->stripe->post('prices/'.$known['id'], ['active' => false]);
            } catch (StripeException) {
                // The old price stays usable for existing subscribers; archiving only hides it.
            }
        }

        $plan->forceFill([
            'stripe_product_id' => $plan->stripe_product_id,
            'stripe_prices' => [...($plan->stripe_prices ?? []), $currency => ['id' => $price['id'], 'fingerprint' => $fingerprint]],
        ])->saveQuietly();

        return $price['id'];
    }
}
