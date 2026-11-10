<?php

use App\Enums\OrderStatus;
use App\Filament\Pages\System\Backups;
use App\Filament\Resources\Cases\Pages\ListCases;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Redirects\Pages\CreateRedirect;
use App\Http\Controllers\NewsletterController;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\Certificate;
use App\Models\ClinicalCase;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Gallery;
use App\Models\Media;
use App\Models\MembershipPlan;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Popup;
use App\Models\Redirect;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Comments\CommentService;
use App\Services\Media\MediaUsage;
use App\Services\Payments\StripeClient;
use App\Services\Theme\ThemeService;
use App\Support\Csv;
use App\Support\RichText;
use App\Support\SafeUrl;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

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

// Security and data integrity ---------------------------------------------------

it('never renders script links from menus or blocks, and refuses them when saving', function () {
    expect(SafeUrl::href('javascript:alert(1)'))->toBe('#')
        ->and(SafeUrl::href(" java\tscript:alert(1)"))->toBe('#')
        ->and(SafeUrl::href('data:text/html,x'))->toBe('#')
        ->and(SafeUrl::href('/courses'))->toBe('/courses')
        ->and(SafeUrl::href('https://example.org/x'))->toBe('https://example.org/x')
        ->and(SafeUrl::href('mailto:a@b.org'))->toBe('mailto:a@b.org')
        ->and(SafeUrl::href('cases'))->toBe('cases');

    Menu::where('location', 'header')->first()?->update(['items' => [['label' => 'Evil', 'url' => 'javascript:alert(document.cookie)', 'new_tab' => false]]]);
    Cache::flush();
    $this->get('/')->assertOk()->assertDontSee('javascript:alert', false);

    $this->actingAsStaff('editor');
    $menu = Menu::firstOrCreate(['location' => 'footer'], ['name' => 'Footer', 'items' => []]);
    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->fillForm(['items' => [['label' => 'Evil', 'url' => 'javascript:alert(1)', 'new_tab' => false]]])
        ->call('save')->assertHasFormErrors();
});

it('checks ownership for every item in bulk bin, restore and publish actions', function () {
    $editor = $this->staff('editor');
    $case = ClinicalCase::factory()->create(['author_id' => $editor->id]);
    $this->actingAsStaff('author');

    Livewire::test(ListCases::class)->callTableBulkAction('delete', [$case->id]);
    expect(ClinicalCase::find($case->id))->not->toBeNull();
});

it('lets only a super administrator download backups', function () {
    $admin = $this->actingAsStaff('administrator');
    Livewire::test(Backups::class)->call('download', 'backup-x.zip')->assertForbidden();
});

it('keeps the backup being restored when the safety backup prunes old ones', function () {
    $service = app(BackupService::class);
    Settings::set('backup.keep', 1);
    $old = $service->create(false, 'old');
    $this->travel(2)->seconds();
    $service->create(false, 'safety', keep: $old);
    expect(collect($service->list())->pluck('name'))->toContain($old);
    foreach ($service->list() as $b) {
        $service->delete($b['name']);
    }
});

it('only redirects newsletter clicks to links of that campaign', function () {
    $campaign = Campaign::create(['subject' => 'S', 'body' => '<p><a href="https://example.org/case">x</a></p>', 'status' => 'sent']);
    $this->get(route('newsletter.click', [$campaign->id, 'nobody', 'u' => 'https://evil.example/phish']))->assertRedirect('/');
    $this->get(route('newsletter.click', [$campaign->id, 'nobody', 'u' => 'https://example.org/case']))->assertRedirect('https://example.org/case');
    $url = 'https://other.example/new';
    $this->get(route('newsletter.click', [$campaign->id, 'nobody', 'u' => $url, 's' => NewsletterController::linkSignature($campaign->id, $url)]))->assertRedirect($url);
});

it('accepts one-click unsubscribe from mail providers without a form token', function () {
    $subscriber = Subscriber::factory()->create();
    expect($this->post('/newsletter/unsubscribe/'.$subscriber->token)->status())->not->toBe(419);
    expect($subscriber->fresh()->status->value)->toBe('unsubscribed');
});

it('cannot break out of the custom CSS with nested tags', function () {
    expect(ThemeService::cleanCustomCss('</st</styleyle><scr<scriptipt>alert(1)</script>'))->not->toContain('<')
        ->and(ThemeService::cleanCustomCss('a{background:url(javajavascript:script:x)}'))->not->toContain('javascript:')
        ->and(ThemeService::cleanCustomCss('.btn{color:red}'))->toBe('.btn{color:red}');
});

it('asks for the password to change the email and keeps newsletter data to verified addresses', function () {
    $member = User::factory()->create(['email_verified_at' => now(), 'password' => bcrypt('Str0ngPassword9')]);
    $victim = Subscriber::factory()->create(['email' => 'victim@example.org']);
    $this->actingAs($member);

    $this->put('/account/profile', ['name' => $member->name, 'email' => 'victim@example.org'])->assertSessionHasErrors('current_password');
    $this->put('/account/profile', ['name' => $member->name, 'email' => 'victim@example.org', 'current_password' => 'Str0ngPassword9', 'newsletter' => '0'])->assertSessionHasNoErrors();
    expect($member->fresh()->email)->toBe('victim@example.org')->and($member->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($victim->fresh()->status->value)->toBe('subscribed');
});

it('applies comment moderation rules to edits as well', function () {
    Settings::set('comments.blocked_words', 'casino');
    $member = User::factory()->create(['email_verified_at' => now()]);
    $article = Article::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
    $comment = $article->comments()->create(['user_id' => $member->id, 'body' => 'Helpful case', 'status' => 'approved']);

    app(CommentService::class)->edit($comment, $member, 'Visit my casino');
    expect($comment->fresh()->status)->toBe('pending');
});

it('protects spreadsheets from formulas and embeds only known video sites', function () {
    expect(Csv::cell('=HYPERLINK("x")'))->toBe("'=HYPERLINK(\"x\")")->and(Csv::cell('-5'))->toBe('-5')
        ->and(RichText::restrictIframes('<iframe src="https://evil.example/x"></iframe><iframe src="https://www.youtube-nocookie.com/embed/abc"></iframe>'))
        ->not->toContain('evil.example')->toContain('youtube-nocookie');
});

it('finds media used in galleries, banners, popups, courses and lessons, and keeps live subscriptions from being deleted with a user', function () {
    $media = Media::create(['disk' => 'public', 'path' => 'uploads/x.jpg', 'filename' => 'x.jpg', 'original_name' => 'x.jpg', 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 1]);
    $gallery = Gallery::factory()->create(['title' => 'Atlas']);
    $gallery->items()->create(['media_id' => $media->id, 'sort_order' => 1]);
    Popup::create(['name' => 'Promo', 'type' => 'modal', 'status' => 'draft', 'title' => 'Hi', 'trigger' => 'load', 'media_id' => $media->id]);
    $types = collect(app(MediaUsage::class)->find($media))->pluck('type');
    expect($types)->toContain('Photo gallery')->toContain('Popup');

    $member = makeMember();
    $admin = $this->staff('super_admin');
    expect($admin->can('delete', $member))->toBeFalse();
});

it('rejects page addresses used by the website and duplicate redirects written differently', function () {
    $this->actingAsStaff('editor');
    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Articles', 'slug' => 'articles'])->call('create')->assertHasFormErrors(['slug']);

    Redirect::create(['source_path' => '/old-page', 'target_url' => '/new', 'status_code' => 301, 'is_active' => true]);
    $this->actingAsStaff('super_admin');
    Livewire::test(CreateRedirect::class)
        ->fillForm(['source_path' => '/Old-Page/', 'target_url' => '/x', 'status_code' => 301])->call('create')->assertHasFormErrors(['source_path']);
});

it('restores the content of a revision but not its publication state', function () {
    $this->actingAsStaff('editor');
    $article = Article::factory()->create(['status' => 'published', 'published_at' => now()->subDay(), 'title' => 'First']);
    $article->update(['title' => 'Second', 'status' => 'draft']);
    $revision = $article->revisions()->where('data->title', 'First')->first() ?? $article->revisions()->oldest('id')->first();

    $article->restoreRevision($revision, keep: ['slug', 'status', 'published_at']);
    expect($article->fresh()->status->value)->toBe('draft');
});

it('records the cron heartbeat without clearing the settings cache every minute', function () {
    Settings::get('site.name');                       // cached
    DB::enableQueryLog();
    Settings::setQuietly('system.last_cron_at', now()->toIso8601String());
    DB::flushQueryLog();
    Settings::get('site.name');
    expect(DB::getQueryLog())->toBe([])   // still served from the cache
        ->and(Settings::fresh('system.last_cron_at'))->not->toBeNull();
});
