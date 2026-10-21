<?php

use App\Services\Payments\StripeClient;
use App\Support\Settings;

function enablePayments(): void
{
    Settings::set('payments.enabled', true);
    Settings::set('payments.stripe_secret_key', 'sk_test_123', true);
    Settings::set('payments.stripe_webhook_secret', 'whsec_test', true);
}

/** Posts a correctly signed Stripe event to the webhook endpoint. */
function stripeEvent(string $type, array $object, ?string $id = null)
{
    $payload = json_encode(['id' => $id ?? 'evt_'.uniqid(), 'type' => $type, 'livemode' => false, 'data' => ['object' => $object]]);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => StripeClient::sign($payload, 'whsec_test'),
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

function makeMember(?\App\Models\User $user = null, string $status = 'active'): \App\Models\User
{
    $user ??= \App\Models\User::factory()->create();
    \App\Models\Subscription::create(['user_id' => $user->id, 'membership_plan_id' => \App\Models\MembershipPlan::factory()->create()->id, 'stripe_subscription_id' => 'sub_'.uniqid(), 'status' => $status, 'amount' => 1500, 'currency' => 'EUR', 'interval' => 'month']);

    return $user->fresh();
}
