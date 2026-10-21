<?php

use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->seedRoles());

it('shows prices in the visitor’s currency by country, cookie or fallback', function () {
    $course = Course::factory()->create(['price' => 9900, 'prices' => [['currency' => 'GBP', 'amount' => 8500], ['currency' => 'USD', 'amount' => 10900]]]);

    $this->get($course->urlPath())->assertSee('€99');
    $this->withHeader('CF-IPCountry', 'GB')->get($course->urlPath())->assertSee('£85');
    $this->withHeader('CF-IPCountry', 'CH')->get($course->urlPath())->assertSee('€99'); // CHF not offered → main price
    $this->withHeader('CF-IPCountry', 'GB')->withCookie('currency', 'USD')->get($course->urlPath())->assertSee('$109');
});

it('switches currency from the switcher', function () {
    $this->from('/membership')->post('/currency', ['currency' => 'GBP'])->assertRedirect('/membership')->assertCookie('currency', 'GBP');
    $this->post('/currency', ['currency' => 'JPY'])->assertStatus(422);
});

it('charges in the chosen currency and records the order in it', function () {
    enablePayments();
    Http::fake([
        'api.stripe.com/v1/customers' => Http::response(['id' => 'cus_1']),
        'api.stripe.com/v1/products' => Http::response(['id' => 'prod_1']),
        'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/x']),
    ]);
    $course = Course::factory()->create(['price' => 9900, 'prices' => [['currency' => 'GBP', 'amount' => 8500]]]);

    $this->actingAs(User::factory()->create())->withHeader('CF-IPCountry', 'GB')
        ->post("/checkout/course/{$course->slug}", ['terms' => 1, 'waiver' => 1])->assertRedirect('https://checkout.stripe.com/x');

    expect(Order::sole()->only(['total', 'currency']))->toBe(['total' => 8500, 'currency' => 'GBP']);
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/checkout/sessions') && str_contains($r->body(), 'currency%5D=gbp') && str_contains($r->body(), 'unit_amount%5D=8500'));
});

it('keeps one Stripe price per currency for each plan', function () {
    enablePayments();
    $n = 0;
    Http::fake(['api.stripe.com/v1/products' => Http::response(['id' => 'prod_1']), 'api.stripe.com/v1/prices' => function () use (&$n) { return Http::response(['id' => 'price_'.(++$n)]); }]);
    $plan = MembershipPlan::factory()->create(['price' => 1500, 'prices' => [['currency' => 'USD', 'amount' => 1600]]]);
    $checkout = app(\App\Services\Payments\Checkout::class);

    $eur = $checkout->ensurePlanPrice($plan, 'EUR');
    $usd = $checkout->ensurePlanPrice($plan->fresh(), 'USD');
    expect($eur)->not->toBe($usd)->and($checkout->ensurePlanPrice($plan->fresh(), 'USD'))->toBe($usd)
        ->and(array_keys($plan->fresh()->stripe_prices))->toBe(['EUR', 'USD']);
});

it('lists every currency as an offer in the course structured data', function () {
    $course = Course::factory()->create(['price' => 9900, 'prices' => [['currency' => 'GBP', 'amount' => 8500]]]);

    $this->get($course->urlPath())->assertSee('"priceCurrency":"EUR"', false)->assertSee('"priceCurrency":"GBP"', false);
});
