<?php

use App\Enums\OrderStatus;
use App\Mail\CourseEnrolled;
use App\Mail\MembershipStarted;
use App\Mail\WebinarRegistered;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Webinar;
use App\Services\Payments\StripeAdmin;
use App\Services\Payments\StripeClient;
use App\Services\Payments\StripeReferences;
use App\Support\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seedRoles();
    Mail::fake();
});

function fakeStripe(array $extra = []): void
{
    Http::fake([
        'api.stripe.com/v1/customers' => Http::response(['id' => 'cus_123']),
        'api.stripe.com/v1/products' => Http::response(['id' => 'prod_123']),
        'api.stripe.com/v1/prices' => Http::response(['id' => 'price_123']),
        'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_123']),
        ...$extra,
    ]);
}

function stripeParams(Request $request): array
{
    parse_str($request->body(), $params);

    return $params;
}

// Signatures & encoding --------------------------------------------------------

it('verifies Stripe webhook signatures and rejects tampering and replays', function () {
    $payload = '{"id":"evt_1"}';
    $header = StripeClient::sign($payload, 'whsec_x');

    expect(StripeClient::verifySignature($payload, $header, 'whsec_x'))->toBeTrue()
        ->and(StripeClient::verifySignature($payload.' ', $header, 'whsec_x'))->toBeFalse()
        ->and(StripeClient::verifySignature($payload, $header, 'whsec_other'))->toBeFalse()
        ->and(StripeClient::verifySignature($payload, StripeClient::sign($payload, 'whsec_x', time() - 3600), 'whsec_x'))->toBeFalse()
        ->and(StripeClient::verifySignature($payload, null, 'whsec_x'))->toBeFalse()
        ->and(StripeClient::verifySignature($payload, $header, null))->toBeFalse();
});

it('encodes nested parameters and booleans the way Stripe expects', function () {
    parse_str(StripeClient::encode(['a' => true, 'line_items' => [['price' => 'p', 'quantity' => 1]], 'skip' => null, 'm' => ['k' => false]]), $out);

    expect($out)->toBe(['a' => 'true', 'line_items' => [['price' => 'p', 'quantity' => '1']], 'm' => ['k' => 'false']]);
});

it('rejects unsigned webhooks and ignores duplicate events', function () {
    enablePayments();
    $this->postJson('/stripe/webhook', ['id' => 'evt_x', 'type' => 'invoice.paid'])->assertStatus(400);
    expect(PaymentEvent::count())->toBe(0);

    stripeEvent('ping.unknown', ['id' => 'x'], 'evt_dup')->assertOk();
    stripeEvent('ping.unknown', ['id' => 'x'], 'evt_dup')->assertOk()->assertJson(['duplicate' => true]);
    expect(PaymentEvent::count())->toBe(1)->and(PaymentEvent::first()->processed_at)->not->toBeNull();
});

// Checkout ---------------------------------------------------------------------

it('sends a member to Stripe Checkout with a subscription for the chosen plan', function () {
    enablePayments();
    fakeStripe();
    $user = User::factory()->create();
    $plan = MembershipPlan::factory()->create(['slug' => 'yearly', 'interval' => 'year', 'price' => 12000, 'trial_days' => 14]);

    $this->actingAs($user)->post('/checkout/membership/yearly')->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

    Http::assertSent(function (Request $request) {
        if (! str_ends_with($request->url(), '/checkout/sessions')) {
            return false;
        }
        $p = stripeParams($request);

        return $p['mode'] === 'subscription' && $p['line_items'][0]['price'] === 'price_123' && $p['customer'] === 'cus_123'
            && $p['subscription_data']['trial_period_days'] === '14' && $p['metadata']['type'] === 'membership'
            && str_contains($p['success_url'], '{CHECKOUT_SESSION_ID}') && $request->hasHeader('Idempotency-Key');
    });
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/prices') && stripeParams($r)['recurring']['interval'] === 'year' && stripeParams($r)['unit_amount'] === '12000');
    expect($user->fresh()->stripe_customer_id)->toBe('cus_123')->and($plan->fresh()->stripe_price_id)->toBe('price_123');
});

it('creates a new Stripe price only when the plan price changes', function () {
    enablePayments();
    fakeStripe();
    $plan = MembershipPlan::factory()->create();
    $checkout = app(\App\Services\Payments\Checkout::class);

    $checkout->ensurePlanPrice($plan);
    $checkout->ensurePlanPrice($plan->fresh());
    Http::assertSentCount(2); // product + price, nothing on the second call

    $plan->update(['price' => 1900]);
    $checkout->ensurePlanPrice($plan->fresh());
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/prices') && stripeParams($r)['unit_amount'] === '1900');
});

it('requires consent before buying a course and records a pending order', function () {
    enablePayments();
    fakeStripe();
    $user = User::factory()->create();
    $course = Course::factory()->create(['price' => 9900]);

    $this->actingAs($user)->from($course->url())->post("/checkout/course/{$course->slug}")->assertSessionHasErrors(['terms', 'waiver']);
    Http::assertNothingSent();

    $this->actingAs($user)->post("/checkout/course/{$course->slug}", ['terms' => '1', 'waiver' => '1'])
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

    $order = Order::firstOrFail();
    expect($order->status)->toBe(OrderStatus::Pending)->and($order->total)->toBe(9900)->and($order->consent_at)->not->toBeNull()
        ->and($order->stripe_checkout_session_id)->toBe('cs_test_123')->and($order->number)->toMatch('/^[A-Z]{2}-\d{4}-\d{5}$/');

    Http::assertSent(function (Request $request) use ($order) {
        if (! str_ends_with($request->url(), '/checkout/sessions')) {
            return false;
        }
        $p = stripeParams($request);

        return $p['mode'] === 'payment' && $p['line_items'][0]['price_data']['unit_amount'] === '9900'
            && $p['line_items'][0]['price_data']['tax_behavior'] === 'inclusive' && $p['metadata']['order_id'] === (string) $order->id;
    });
});

it('does not start a payment when Stripe is not configured', function () {
    Http::fake();
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->create())->from($course->url())
        ->post("/checkout/course/{$course->slug}", ['terms' => '1', 'waiver' => '1'])
        ->assertRedirect($course->url())->assertSessionHas('error');
    Http::assertNothingSent();
});

it('shows a friendly error when Stripe rejects the request', function () {
    enablePayments();
    Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'Invalid API Key provided']], 401)]);
    $plan = MembershipPlan::factory()->create(['slug' => 'monthly']);

    $this->actingAs(User::factory()->create())->from('/membership')->post('/checkout/membership/monthly')
        ->assertRedirect('/membership')->assertSessionHas('error');
});

// Fulfilment -------------------------------------------------------------------

function paidCourseOrder(): array
{
    $user = User::factory()->create();
    $course = Course::factory()->create(['price' => 9900]);
    $order = Order::create(['user_id' => $user->id, 'email' => $user->email, 'type' => 'course', 'purchasable_type' => 'course', 'purchasable_id' => $course->id,
        'description' => $course->title, 'subtotal' => 9900, 'total' => 9900, 'currency' => 'EUR', 'status' => 'pending', 'stripe_checkout_session_id' => 'cs_1']);

    return [$user, $course, $order];
}

function courseSession(Order $order, string $status = 'paid'): array
{
    return ['id' => 'cs_1', 'payment_status' => $status, 'payment_intent' => 'pi_1', 'amount_total' => 9900, 'amount_subtotal' => 8250, 'currency' => 'eur',
        'total_details' => ['amount_tax' => 1650, 'amount_discount' => 0], 'customer_details' => ['address' => ['country' => 'DE'], 'tax_ids' => [['value' => 'DE123']]],
        'client_reference_id' => (string) $order->user_id, 'metadata' => ['type' => 'course', 'order_id' => (string) $order->id, 'user_id' => (string) $order->user_id, 'item_id' => (string) $order->purchasable_id]];
}

it('enrols the buyer when Stripe confirms a course payment, exactly once', function () {
    enablePayments();
    Http::fake(['api.stripe.com/v1/payment_intents/*' => Http::response(['latest_charge' => ['receipt_url' => 'https://pay.stripe.com/receipts/r1']])]);
    [$user, $course, $order] = paidCourseOrder();

    stripeEvent('checkout.session.completed', courseSession($order), 'evt_a')->assertOk();
    stripeEvent('checkout.session.completed', courseSession($order), 'evt_b')->assertOk(); // a second delivery of the same outcome

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid)->and($order->tax)->toBe(1650)->and($order->stripe_payment_intent_id)->toBe('pi_1')
        ->and($order->receipt_url)->toBe('https://pay.stripe.com/receipts/r1')->and($order->meta['customer_country'])->toBe('DE')
        ->and(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->value('source'))->toBe('purchase')
        ->and($course->fresh()->enrollments_count)->toBe(1);
    Mail::assertQueued(CourseEnrolled::class, 1);
});

it('waits for delayed payment methods before granting access', function () {
    enablePayments();
    Http::fake();
    [$user, $course, $order] = paidCourseOrder();

    stripeEvent('checkout.session.completed', courseSession($order, 'unpaid'))->assertOk();
    expect(Enrollment::count())->toBe(0)->and($order->fresh()->status)->toBe(OrderStatus::Pending);

    stripeEvent('checkout.session.async_payment_succeeded', courseSession($order))->assertOk();
    expect(Enrollment::count())->toBe(1);
});

it('marks abandoned checkouts', function () {
    enablePayments();
    [, , $order] = paidCourseOrder();

    stripeEvent('checkout.session.expired', ['id' => 'cs_1', 'metadata' => []])->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatus::Canceled);
});

it('gives the webinar place after a ticket purchase', function () {
    enablePayments();
    Http::fake();
    $user = User::factory()->create();
    $webinar = Webinar::factory()->paid()->create();
    $order = Order::create(['user_id' => $user->id, 'email' => $user->email, 'type' => 'webinar', 'purchasable_type' => 'webinar', 'purchasable_id' => $webinar->id,
        'description' => 'Ticket', 'total' => 2500, 'currency' => 'EUR', 'status' => 'pending']);

    stripeEvent('checkout.session.completed', ['id' => 'cs_w', 'payment_status' => 'paid', 'payment_intent' => 'pi_w', 'amount_total' => 2500, 'currency' => 'eur',
        'metadata' => ['type' => 'webinar', 'order_id' => (string) $order->id, 'user_id' => (string) $user->id]])->assertOk();

    expect($webinar->registrations()->where('user_id', $user->id)->value('source'))->toBe('purchase')
        ->and($webinar->fresh()->registrations_count)->toBe(1);
    Mail::assertQueued(WebinarRegistered::class, fn ($mail) => count($mail->attachments()) === 1);
});

function subscriptionObject(User $user, MembershipPlan $plan, string $status = 'active', bool $basil = true): array
{
    $item = ['price' => ['id' => 'price_123', 'unit_amount' => $plan->price, 'currency' => 'eur', 'recurring' => ['interval' => $plan->interval]]];
    $periodEnd = now()->addMonth()->timestamp;

    return [
        'id' => 'sub_1', 'customer' => 'cus_1', 'status' => $status, 'cancel_at_period_end' => false,
        'metadata' => ['user_id' => (string) $user->id, 'plan_id' => (string) $plan->id],
        'items' => ['data' => [$basil ? [...$item, 'current_period_end' => $periodEnd] : $item]],
        ...($basil ? [] : ['current_period_end' => $periodEnd]),
    ];
}

it('activates membership from subscription events in both Stripe API shapes', function (bool $basil) {
    enablePayments();
    $user = User::factory()->create();
    $plan = MembershipPlan::factory()->create();

    stripeEvent('customer.subscription.created', subscriptionObject($user, $plan, 'active', $basil))->assertOk();

    $subscription = Subscription::firstOrFail();
    expect($user->fresh()->isMember())->toBeTrue()->and($subscription->current_period_end)->not->toBeNull()
        ->and($subscription->amount)->toBe(1500)->and($user->fresh()->stripe_customer_id)->toBe('cus_1');
    Mail::assertQueued(MembershipStarted::class, 1);

    stripeEvent('customer.subscription.updated', subscriptionObject($user, $plan, 'past_due', $basil))->assertOk();
    expect($user->fresh()->isMember())->toBeTrue(); // grace period while Stripe retries

    stripeEvent('customer.subscription.deleted', subscriptionObject($user, $plan, 'canceled', $basil))->assertOk();
    expect($user->fresh()->isMember())->toBeFalse();
    Mail::assertQueued(MembershipStarted::class, 1);
})->with(['2025 API shape' => true, 'classic API shape' => false]);

it('sends the welcome email when a subscription becomes active after 3-D Secure', function () {
    enablePayments();
    $user = User::factory()->create();
    $plan = MembershipPlan::factory()->create();

    stripeEvent('customer.subscription.created', subscriptionObject($user, $plan, 'incomplete'));
    Mail::assertNothingQueued();
    stripeEvent('customer.subscription.updated', subscriptionObject($user, $plan, 'active'));
    Mail::assertQueued(MembershipStarted::class, 1);
});

it('records paid membership invoices as orders for revenue reports', function () {
    enablePayments();
    $user = User::factory()->create(['stripe_customer_id' => 'cus_1']);
    $plan = MembershipPlan::factory()->create();
    stripeEvent('customer.subscription.created', subscriptionObject($user, $plan));

    $invoice = ['id' => 'in_1', 'customer' => 'cus_1', 'amount_paid' => 1500, 'subtotal' => 1250, 'total_taxes' => [['amount' => 250]], 'currency' => 'eur',
        'billing_reason' => 'subscription_cycle', 'hosted_invoice_url' => 'https://invoice', 'invoice_pdf' => 'https://invoice.pdf',
        'parent' => ['subscription_details' => ['subscription' => 'sub_1']], 'status_transitions' => ['paid_at' => now()->timestamp]];
    stripeEvent('invoice.paid', $invoice)->assertOk();
    stripeEvent('invoice.paid', $invoice)->assertOk();
    stripeEvent('invoice.paid', [...$invoice, 'id' => 'in_trial', 'amount_paid' => 0])->assertOk();

    $order = Order::sole();
    expect($order->type)->toBe('membership')->and($order->total)->toBe(1500)->and($order->tax)->toBe(250)
        ->and($order->user_id)->toBe($user->id)->and($order->invoice_pdf_url)->toBe('https://invoice.pdf');
});

it('removes purchased access after a full refund but not a partial one', function () {
    enablePayments();
    Http::fake();
    [$user, $course, $order] = paidCourseOrder();
    stripeEvent('checkout.session.completed', courseSession($order));

    stripeEvent('charge.refunded', ['id' => 'ch_1', 'payment_intent' => 'pi_1', 'amount_refunded' => 2000]);
    expect($order->fresh()->status)->toBe(OrderStatus::PartiallyRefunded)->and(Enrollment::count())->toBe(1);

    stripeEvent('charge.refunded', ['id' => 'ch_1', 'payment_intent' => 'pi_1', 'amount_refunded' => 9900]);
    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)->and(Enrollment::count())->toBe(0);
});

it('grants access on the success page without waiting for the webhook', function () {
    enablePayments();
    [$user, $course, $order] = paidCourseOrder();
    Http::fake([
        'api.stripe.com/v1/checkout/sessions/*' => Http::response(courseSession($order)),
        'api.stripe.com/v1/payment_intents/*' => Http::response(['latest_charge' => ['receipt_url' => null]]),
    ]);

    $this->actingAs($user)->get('/checkout/success?session_id=cs_1')->assertRedirect(route('courses.show', $course->slug));
    expect(Enrollment::where('user_id', $user->id)->exists())->toBeTrue();

    $this->actingAs(User::factory()->create())->get('/checkout/success?session_id=cs_1')->assertForbidden();
});

it('refunds from the dashboard through the Stripe API', function () {
    enablePayments();
    Http::fake(['api.stripe.com/v1/refunds' => Http::response(['id' => 're_1'])]);
    [$user, $course, $order] = paidCourseOrder();
    $order->update(['status' => 'paid', 'stripe_payment_intent_id' => 'pi_1', 'paid_at' => now()]);
    app(\App\Services\Learning\LearningService::class)->enroll($user, $course, 'purchase', $order, false);
    $this->actingAs($this->staff());

    app(StripeAdmin::class)->refund($order->fresh(), 9900, 'requested_by_customer');

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/refunds') && stripeParams($r)['payment_intent'] === 'pi_1' && stripeParams($r)['amount'] === '9900');
    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)->and(Enrollment::count())->toBe(0);
});

it('forgets test-mode Stripe IDs when switching to live keys', function () {
    Settings::set('payments.stripe_secret_key', 'sk_test_1', true);
    StripeReferences::syncMode();
    $user = User::factory()->create(['stripe_customer_id' => 'cus_test']);
    $plan = MembershipPlan::factory()->create();
    $plan->forceFill(['stripe_price_id' => 'price_test', 'stripe_product_id' => 'prod_test'])->save();

    Settings::set('payments.stripe_secret_key', 'sk_live_1', true);
    expect(StripeReferences::syncMode())->toBeTrue()
        ->and($user->fresh()->stripe_customer_id)->toBeNull()->and($plan->fresh()->stripe_price_id)->toBeNull();
});

it('handles Stripe being unreachable without an error page', function () {
    enablePayments();
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'));
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->create())->from($course->url())
        ->post("/checkout/course/{$course->slug}", ['terms' => '1', 'waiver' => '1'])
        ->assertRedirect($course->url())->assertSessionHas('error');
});
