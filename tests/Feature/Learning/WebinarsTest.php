<?php

use App\Mail\WebinarRegistered;
use App\Mail\WebinarReminder;
use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarRegistration;
use App\Services\Learning\WebinarReminders;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seedRoles();
    Mail::fake();
});

it('renders the webinar page with Event structured data but never the join link', function () {
    $webinar = Webinar::factory()->create(['title' => 'Retina live']);

    $this->get('/webinars')->assertOk()->assertSee('Retina live');
    $this->get($webinar->urlPath())->assertOk()->assertSee('"@type":"Event"', false)->assertDontSee('9876543210');
    $this->get("/webinars/{$webinar->slug}/calendar.ics")->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VEVENT')->assertDontSee('9876543210');
});

it('registers for a free webinar and emails a calendar invitation', function () {
    $user = User::factory()->create();
    $webinar = Webinar::factory()->create();

    $this->actingAs($user)->post("/webinars/{$webinar->slug}/register")->assertSessionHas('status');
    $this->actingAs($user)->post("/webinars/{$webinar->slug}/register");

    expect(WebinarRegistration::sole()->source)->toBe('free')->and($webinar->fresh()->registrations_count)->toBe(1);
    Mail::assertQueued(WebinarRegistered::class, 1);
    $this->actingAs($user)->get($webinar->urlPath())->assertSee('You are registered');
});

it('lets members register for paid webinars for free but not other users', function () {
    $webinar = Webinar::factory()->paid()->create();

    $this->actingAs(User::factory()->create())->post("/webinars/{$webinar->slug}/register")->assertForbidden();
    $this->actingAs(makeMember())->post("/webinars/{$webinar->slug}/register")->assertSessionHas('status');
    expect(WebinarRegistration::sole()->source)->toBe('membership');
});

it('closes registration when the webinar is full', function () {
    $webinar = Webinar::factory()->create(['capacity' => 1]);
    $this->actingAs(User::factory()->create())->post("/webinars/{$webinar->slug}/register");

    $this->actingAs(User::factory()->create())->post("/webinars/{$webinar->slug}/register")->assertSessionHas('error');
    expect(WebinarRegistration::count())->toBe(1);
});

it('opens the personal join link only for registrants and only near the start', function () {
    $user = User::factory()->create();
    $webinar = Webinar::factory()->create(['starts_at' => now()->addDay()]);

    $this->actingAs($user)->get("/webinars/{$webinar->slug}/join")->assertRedirect($webinar->url());
    $this->actingAs($user)->post("/webinars/{$webinar->slug}/register");
    $this->actingAs($user)->get("/webinars/{$webinar->slug}/join")->assertRedirect($webinar->url())->assertSessionHas('status');

    $webinar->update(['starts_at' => now()->addMinutes(10)]);
    $this->actingAs($user)->get("/webinars/{$webinar->slug}/join")->assertRedirect('https://zoom.us/j/9876543210?pwd=secret');
    expect(WebinarRegistration::sole()->joined_at)->not->toBeNull();
});

it('stores the join link encrypted', function () {
    $webinar = Webinar::factory()->create();

    expect(\Illuminate\Support\Facades\DB::table('webinars')->where('id', $webinar->id)->value('join_url'))->not->toContain('zoom.us');
});

it('sends day and hour reminders once each', function () {
    $user = User::factory()->create();
    $webinar = Webinar::factory()->create(['starts_at' => now()->addHours(20)]);
    WebinarRegistration::create(['webinar_id' => $webinar->id, 'user_id' => $user->id, 'source' => 'free'])
        ->forceFill(['created_at' => now()->subDays(3)])->save();

    expect(app(WebinarReminders::class)->send())->toBe(1)->and(app(WebinarReminders::class)->send())->toBe(0);
    Mail::assertQueued(WebinarReminder::class, fn ($m) => $m->when === 'day');

    $webinar->update(['starts_at' => now()->addMinutes(50)]);
    expect(app(WebinarReminders::class)->send())->toBe(1)->and(app(WebinarReminders::class)->send())->toBe(0);
    Mail::assertQueued(WebinarReminder::class, fn ($m) => $m->when === 'hour');
});

it('shows recordings to registrants and members only', function () {
    $webinar = Webinar::factory()->paid()->create(['starts_at' => now()->subDays(2), 'recording_url' => 'https://www.youtube.com/watch?v=RECORDING01']);

    $this->get($webinar->urlPath())->assertOk()->assertDontSee('RECORDING01');
    $this->actingAs(User::factory()->create())->get($webinar->urlPath())->assertDontSee('RECORDING01');
    $this->actingAs(makeMember())->get($webinar->urlPath())->assertSee('RECORDING01');
});
