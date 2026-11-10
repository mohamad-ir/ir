<?php

use App\Enums\OrderStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\StripeClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/* Regression tests for issues found in the 1.7.0 release QA. */

beforeEach(fn () => $this->seedRoles());

it('sends the security headers on dashboard pages too, including the sign-in page', function () {
    foreach (['/admin/login', '/admin/password-reset/request'] as $url) {
        $this->get($url)->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
    $this->actingAsStaff();
    $this->get('/admin')->assertOk()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

// Payments ---------------------------------------------------------------------

function qaStripeEvent(string $type, array $object, int $created)
{
    $payload = json_encode(['id' => 'evt_'.uniqid(), 'type' => $type, 'created' => $created, 'livemode' => false, 'data' => ['object' => $object]]);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => StripeClient::sign($payload, 'whsec_test'),
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

function qaSubscription(User $user, MembershipPlan $plan, string $status): array
{
    return ['id' => 'sub_qa', 'customer' => 'cus_qa', 'status' => $status, 'cancel_at_period_end' => false,
        'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id],
        'items' => ['data' => [['price' => ['id' => 'price_qa', 'unit_amount' => 1500, 'currency' => 'eur', 'recurring' => ['interval' => 'month']], 'current_period_end' => now()->addMonth()->timestamp]]]];
}

it('applies Stripe subscription events in the order they happened, not the order they arrive', function () {
    enablePayments();
    Mail::fake();
    $user = User::factory()->create();
    $plan = MembershipPlan::factory()->create();
    $t = now()->timestamp;

    // Paid member: "active" arrives before the older "incomplete".
    qaStripeEvent('customer.subscription.updated', qaSubscription($user, $plan, 'active'), $t)->assertOk();
    qaStripeEvent('customer.subscription.created', qaSubscription($user, $plan, 'incomplete'), $t - 5)->assertOk();
    expect($user->fresh()->isMember())->toBeTrue();

    // Cancelled: a retried older "active" must not bring access back.
    qaStripeEvent('customer.subscription.deleted', qaSubscription($user, $plan, 'canceled'), $t + 60)->assertOk();
    qaStripeEvent('customer.subscription.updated', qaSubscription($user, $plan, 'active'), $t + 30)->assertOk();
    expect($user->fresh()->isMember())->toBeFalse()
        ->and(Subscription::count())->toBe(1);
});

it('links membership payments to their payment intent with the 2025 Stripe API, so they can be refunded', function () {
    enablePayments();
    Mail::fake();
    Http::fake(['api.stripe.com/v1/invoices/in_qa*' => Http::response([
        'id' => 'in_qa', 'payments' => ['data' => [['payment' => ['type' => 'payment_intent', 'payment_intent' => 'pi_member']]]],
    ])]);
    $user = User::factory()->create(['stripe_customer_id' => 'cus_qa']);
    $plan = MembershipPlan::factory()->create();
    qaStripeEvent('customer.subscription.created', qaSubscription($user, $plan, 'active'), now()->timestamp);

    qaStripeEvent('invoice.paid', ['id' => 'in_qa', 'customer' => 'cus_qa', 'amount_paid' => 1500, 'currency' => 'eur', 'billing_reason' => 'subscription_create',
        'parent' => ['subscription_details' => ['subscription' => 'sub_qa']]], now()->timestamp)->assertOk();
    $order = Order::sole();
    expect($order->stripe_payment_intent_id)->toBe('pi_member');

    qaStripeEvent('charge.refunded', ['id' => 'ch_m', 'payment_intent' => 'pi_member', 'amount_refunded' => 1500], now()->timestamp)->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

it('fulfils orders paid entirely with a promotion code, flags paying twice and treats a lost chargeback as a refund', function () {
    enablePayments();
    Mail::fake();
    Http::fake();
    $user = User::factory()->create();
    $course = Course::factory()->create(['price' => 9900]);
    $order = fn (string $session) => Order::create(['user_id' => $user->id, 'email' => $user->email, 'type' => 'course', 'purchasable_type' => 'course', 'purchasable_id' => $course->id,
        'description' => $course->title, 'subtotal' => 9900, 'total' => 9900, 'currency' => 'EUR', 'status' => 'pending', 'stripe_checkout_session_id' => $session]);
    $session = fn (Order $o, string $status, string $pi) => ['id' => $o->stripe_checkout_session_id, 'payment_status' => $status, 'payment_intent' => $pi, 'amount_total' => 9900, 'currency' => 'eur',
        'metadata' => ['type' => 'course', 'order_id' => (string) $o->id, 'user_id' => (string) $user->id, 'item_id' => (string) $course->id]];

    $first = $order('cs_free');
    qaStripeEvent('checkout.session.completed', $session($first, 'no_payment_required', 'pi_free'), now()->timestamp)->assertOk();
    expect($first->fresh()->isPaid())->toBeTrue()
        ->and(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeTrue();

    $second = $order('cs_again');
    qaStripeEvent('checkout.session.completed', $session($second, 'paid', 'pi_again'), now()->timestamp)->assertOk();
    expect($second->fresh()->meta['duplicate_of'] ?? null)->toBe($first->number);

    // A certificate earned with the purchase is revoked when the payment is lost in a chargeback.
    $certificate = Certificate::create(['code' => 'QA-CERT', 'user_id' => $user->id, 'course_id' => $course->id, 'recipient_name' => $user->name, 'course_title' => $course->title, 'issued_at' => now()]);
    Enrollment::where('user_id', $user->id)->update(['order_id' => $second->id]);
    qaStripeEvent('charge.dispute.closed', ['id' => 'dp_1', 'payment_intent' => 'pi_again', 'status' => 'lost'], now()->timestamp)->assertOk();
    expect($second->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($certificate->fresh()->revoked_at)->not->toBeNull();
});

it('closes earlier unpaid checkout pages for the same course before opening a new one', function () {
    enablePayments();
    Http::fake([
        'api.stripe.com/v1/checkout/sessions/cs_old/expire' => Http::response(['id' => 'cs_old', 'status' => 'expired']),
        'api.stripe.com/v1/customers' => Http::response(['id' => 'cus_123']),
        'api.stripe.com/v1/products' => Http::response(['id' => 'prod_123']),
        'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_new', 'url' => 'https://checkout.stripe.com/c/pay/cs_new']),
    ]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $course = Course::factory()->create(['price' => 9900, 'status' => 'published', 'published_at' => now()->subDay()]);
    $old = Order::create(['user_id' => $user->id, 'email' => $user->email, 'type' => 'course', 'purchasable_type' => 'course', 'purchasable_id' => $course->id,
        'description' => $course->title, 'total' => 9900, 'currency' => 'EUR', 'status' => 'pending', 'stripe_checkout_session_id' => 'cs_old']);

    $this->actingAs($user)->post(route('checkout.course', $course->slug), ['terms' => '1', 'waiver' => '1'])->assertRedirect('https://checkout.stripe.com/c/pay/cs_new');
    expect($old->fresh()->status)->toBe(OrderStatus::Canceled);
    Http::assertSent(fn ($r) => str_ends_with($r->url(), 'checkout/sessions/cs_old/expire'));
});

it('shows a “processing” page instead of an error when fulfilment fails on the success page', function () {
    enablePayments();
    $user = User::factory()->create();
    Http::fake(['api.stripe.com/v1/checkout/sessions/cs_x' => Http::response([
        'id' => 'cs_x', 'client_reference_id' => (string) $user->id, 'payment_status' => 'paid', 'subscription' => 'sub_broken', 'metadata' => ['type' => 'membership', 'user_id' => (string) $user->id]])]);
    Http::fake(['api.stripe.com/v1/subscriptions/sub_broken' => Http::response(['error' => ['message' => 'boom']], 500)]);

    $this->actingAs($user)->get(route('checkout.success').'?session_id=cs_x')->assertOk();
});
