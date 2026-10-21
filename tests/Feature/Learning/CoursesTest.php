<?php

use App\Enums\AccessLevel;
use App\Mail\CourseEnrolled;
use App\Models\Article;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;
use App\Services\Access\ContentAccess;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seedRoles();
    Mail::fake();
});


it('lists published courses and renders the course page with Course structured data', function () {
    $course = Course::factory()->withLessons(3)->create(['title' => 'OCT Masterclass', 'slug' => 'oct-masterclass']);
    Course::factory()->create(['status' => 'draft', 'title' => 'Hidden draft course']);

    $this->get('/courses')->assertOk()->assertSee('OCT Masterclass')->assertDontSee('Hidden draft course');
    $this->get('/courses/oct-masterclass')->assertOk()
        ->assertSee('"@type":"Course"', false)->assertSee('Lesson 1')->assertSee('Free preview')->assertSee('€49');
});

it('shows preview lessons to everyone but hides locked lesson videos', function () {
    $course = Course::factory()->withLessons(2, previews: 1)->create();

    $this->get("/courses/{$course->slug}/lessons/lesson-1")->assertOk()->assertSee('SECRETvid01');
    $this->get("/courses/{$course->slug}/lessons/lesson-2")->assertOk()->assertDontSee('SECRETvid02')->assertSee('Enrol to unlock');
});

it('enrols in a free course, tracks progress and completes it', function () {
    $user = User::factory()->create();
    $course = Course::factory()->free()->withLessons(2, 0)->create();

    $this->actingAs($user)->post("/courses/{$course->slug}/enroll")->assertRedirect(route('courses.lesson', [$course->slug, 'lesson-1']));
    expect(Enrollment::sole()->source)->toBe('free');
    Mail::assertQueued(CourseEnrolled::class);

    $this->actingAs($user)->get("/courses/{$course->slug}/lessons/lesson-2")->assertOk()->assertSee('SECRETvid02');
    $this->actingAs($user)->post("/courses/{$course->slug}/lessons/lesson-1/complete", ['continue' => 1])->assertRedirect(route('courses.lesson', [$course->slug, 'lesson-2']));
    $this->actingAs($user)->post("/courses/{$course->slug}/lessons/lesson-2/complete");

    expect(Enrollment::sole()->completed_at)->not->toBeNull()
        ->and(app(\App\Services\Learning\LearningService::class)->progress($user, $course))->toBe(100);
    $this->actingAs($user)->get('/account')->assertOk()->assertSee($course->title)->assertSee('100%');
});

it('does not let non-buyers enrol in or complete paid courses', function () {
    $user = User::factory()->create();
    $course = Course::factory()->withLessons(1, 0)->create(['included_in_membership' => false]);

    $this->actingAs($user)->post("/courses/{$course->slug}/enroll")->assertForbidden();
    $this->actingAs($user)->post("/courses/{$course->slug}/lessons/lesson-1/complete")->assertForbidden();
    expect(Enrollment::count())->toBe(0);
});

it('gives members access to included courses only while the membership lasts', function () {
    $member = makeMember();
    $included = Course::factory()->withLessons(1, 0)->create();
    $excluded = Course::factory()->withLessons(1, 0)->create(['included_in_membership' => false]);
    $access = app(ContentAccess::class);

    expect($access->canAccessCourse($included, $member))->toBeTrue()->and($access->canAccessCourse($excluded, $member))->toBeFalse();

    $this->actingAs($member)->get("/courses/{$included->slug}/lessons/lesson-1")->assertSee('SECRETvid01');
    expect(Enrollment::sole()->source)->toBe('membership'); // created on first visit for progress tracking

    $member->subscriptions()->update(['status' => 'canceled']);
    expect($access->canAccessCourse($included, $member->fresh()))->toBeFalse();
});

it('keeps purchased courses after the membership ends', function () {
    $member = makeMember();
    $course = Course::factory()->withLessons(1, 0)->create();
    app(\App\Services\Learning\LearningService::class)->enroll($member, $course, 'membership', notify: false);
    app(\App\Services\Learning\LearningService::class)->enroll($member, $course, 'purchase', notify: false);
    $member->subscriptions()->update(['status' => 'canceled']);

    expect(Enrollment::sole()->source)->toBe('purchase')
        ->and(app(ContentAccess::class)->canAccessCourse($course, $member->fresh()))->toBeTrue();
});

it('gates members-only library content behind membership', function () {
    $video = Video::factory()->create(['access_level' => AccessLevel::Members]);
    $article = Article::factory()->create(['access_level' => AccessLevel::Members]);

    $this->get($video->urlPath())->assertOk()->assertSee('included with membership')->assertDontSee('abcdefghijk');
    $this->actingAs(User::factory()->create())->get($article->urlPath())->assertOk()->assertSee('See membership options');
    $this->actingAs(makeMember())->get($video->urlPath())->assertOk()->assertDontSee('included with membership');
});

it('shows the membership page with plans and opening-soon buttons until payments are enabled', function () {
    MembershipPlan::factory()->create(['name' => 'Yearly plan', 'interval' => 'year', 'price' => 12000]);
    MembershipPlan::factory()->create(['name' => 'Monthly plan', 'interval' => 'month', 'price' => 1500]);

    $this->get('/membership')->assertOk()->assertSee('Yearly plan')->assertSee('€120')->assertSee('Save 33%')->assertSee('Opening soon');
    enablePayments();
    $this->actingAs(User::factory()->create())->get('/membership')->assertOk()->assertSee('Become a member')->assertDontSee('Opening soon');
});

it('stops account deletion while a membership is still renewing', function () {
    $member = makeMember(User::factory()->create(['password' => 'Password123']));

    $this->actingAs($member)->delete('/account', ['password' => 'Password123'])->assertSessionHasErrors('password');
    expect(User::find($member->id))->not->toBeNull();
});
