<?php

use App\Filament\Pages\Settings\PaymentSettings;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\MembershipPlans\Pages\CreateMembershipPlan;
use App\Filament\Resources\Webinars\Pages\EditWebinar;
use App\Models\Course;
use App\Models\Menu;
use App\Models\MembershipPlan;
use App\Models\Order;
use App\Models\Page;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Webinar;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

it('loads every sales and learning page in the dashboard', function (string $path) {
    $this->seed(DatabaseSeeder::class);
    $course = Course::factory()->withLessons(2)->create();
    $webinar = Webinar::factory()->create();
    $plan = MembershipPlan::factory()->create();
    $user = User::factory()->create();
    $order = Order::create(['user_id' => $user->id, 'email' => $user->email, 'type' => 'course', 'purchasable_type' => 'course', 'purchasable_id' => $course->id,
        'description' => 'Course', 'total' => 4900, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => now(), 'stripe_payment_intent_id' => 'pi_1']);
    Subscription::create(['user_id' => $user->id, 'membership_plan_id' => $plan->id, 'stripe_subscription_id' => 'sub_1', 'status' => 'active', 'amount' => 1500, 'currency' => 'EUR', 'interval' => 'month']);
    $this->actingAsStaff();

    $path = strtr($path, ['{course}' => $course->id, '{webinar}' => $webinar->id, '{plan}' => $plan->id, '{order}' => $order->id]);
    $this->get('/'.config('cms.admin_path').$path)->assertOk();
})->with(['', '/courses', '/courses/create', '/courses/{course}/edit', '/webinars', '/webinars/create', '/webinars/{webinar}/edit',
    '/membership-plans', '/membership-plans/create', '/membership-plans/{plan}/edit', '/orders', '/orders/{order}', '/subscriptions', '/settings/payments']);

it('creates a course with a price entered in euros', function () {
    $this->actingAsStaff();

    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Retina basics', 'slug' => 'retina-basics', 'price' => '49.50', 'currency' => 'EUR', 'status' => 'published', 'level' => 'beginner',
            'outcomes' => [['text' => 'Read OCT']]])
        ->call('create')->assertHasNoFormErrors();

    $course = Course::where('slug', 'retina-basics')->firstOrFail();
    expect($course->price)->toBe(4950)->and($course->outcomes)->toBe([['text' => 'Read OCT']])->and($course->isPublished())->toBeTrue();
    auth()->logout();
    $this->get('/courses/retina-basics')->assertOk()->assertSee('€49.50');
});

it('loads saved learning outcomes back into the course form', function () {
    $this->actingAsStaff();
    $course = Course::factory()->create(['outcomes' => [['text' => 'Spot subretinal fluid']]]);

    Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
        ->assertSchemaStateSet(fn (array $state) => expect(collect($state['outcomes'])->pluck('text')->all())->toBe(['Spot subretinal fluid']) ? [] : []);
});

it('adds a lesson from the course editor', function () {
    $this->actingAsStaff();
    $course = Course::factory()->create();

    Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
        ->callTableAction('create', data: ['title' => 'Intro to OCT', 'video_provider' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'duration_minutes' => 7])
        ->assertHasNoTableActionErrors();

    $lesson = $course->lessons()->sole();
    expect($lesson->slug)->toBe('intro-to-oct')->and($lesson->provider_video_id)->toBe('dQw4w9WgXcQ')->and($lesson->sort_order)->toBe(1);
});

it('keeps the private join link when a webinar is edited', function () {
    $this->actingAsStaff();
    $webinar = Webinar::factory()->create();

    Livewire::test(EditWebinar::class, ['record' => $webinar->getRouteKey()])
        ->fillForm(['title' => 'Renamed webinar'])->call('save')->assertHasNoFormErrors();

    expect($webinar->fresh()->title)->toBe('Renamed webinar')->and($webinar->fresh()->join_url)->toBe('https://zoom.us/j/9876543210?pwd=secret');
});

it('creates a membership plan', function () {
    $this->actingAsStaff();

    Livewire::test(CreateMembershipPlan::class)
        ->fillForm(['name' => 'Yearly', 'slug' => 'yearly', 'price' => '120', 'currency' => 'EUR', 'interval' => 'year', 'features' => [['text' => 'Everything']]])
        ->call('create')->assertHasNoFormErrors();

    expect(MembershipPlan::sole()->price)->toBe(12000);
});

it('refuses to switch payments on without a secret key and never shows saved keys', function () {
    $this->actingAsStaff();

    Livewire::test(PaymentSettings::class)->fillForm(['payments.enabled' => true])->call('save')->assertHasErrors();
    expect(Settings::get('payments.enabled'))->toBeFalse();

    Livewire::test(PaymentSettings::class)->fillForm(['payments.enabled' => true, 'payments.stripe_secret_key' => 'sk_test_abc123'])->call('save')->assertHasNoErrors();
    Settings::flush();
    expect(Settings::get('payments.enabled'))->toBeTrue()->and(Settings::get('payments.stripe_secret_key'))->toBe('sk_test_abc123')
        ->and(\App\Models\Setting::where('key', 'payments.stripe_secret_key')->value('value'))->not->toContain('sk_test_abc123');

    Livewire::test(PaymentSettings::class)->assertSchemaStateSet(['payments.stripe_secret_key' => null]);
});

it('limits sales data to people with sales permissions', function () {
    $this->actingAsStaff('editor');
    $this->get('/'.config('cms.admin_path').'/courses')->assertOk();
    $this->get('/'.config('cms.admin_path').'/orders')->assertOk();
    $this->get('/'.config('cms.admin_path').'/settings/payments')->assertForbidden();
    $this->get('/'.config('cms.admin_path').'/membership-plans/create')->assertForbidden();

    $this->actingAsStaff('author');
    $this->get('/'.config('cms.admin_path').'/orders')->assertForbidden();
    $this->get('/'.config('cms.admin_path').'/courses')->assertForbidden();
});

it('upgrades an existing site: menus, legal pages and the old courses placeholder', function () {
    $this->seed(DatabaseSeeder::class);
    // Recreate the 1.0 state: a "coming soon" page at /courses and the old footer link.
    Page::where('slug', 'terms-of-sale')->forceDelete();
    Page::create(['title' => 'Courses', 'slug' => 'courses', 'status' => 'published', 'published_at' => now(), 'blocks' => []]);
    Menu::where('location', 'header')->first()->update(['items' => [['label' => 'About', 'url' => '/about']]]);
    Menu::where('location', 'footer_tertiary')->first()->update(['items' => [['label' => 'Courses (coming soon)', 'url' => '/courses']]]);

    (require database_path('migrations/2026_10_21_100100_update_existing_sites_for_learning.php'))->up();

    expect(Page::where('slug', 'courses-coming-soon')->value('status')->value)->toBe('draft')
        ->and(Page::where('slug', 'terms-of-sale')->exists())->toBeTrue()
        ->and(collect(Menu::where('location', 'header')->first()->items)->pluck('url')->all())->toBe(['/courses', '/about'])
        ->and(collect(Menu::where('location', 'footer_tertiary')->first()->items)->pluck('label')->all())->toBe(['Courses', 'Webinars', 'Membership']);
    $this->get('/courses')->assertOk()->assertSee('Structured online courses');
});
