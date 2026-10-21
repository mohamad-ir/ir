<?php

use App\Livewire\ContactForm;
use App\Livewire\NewsletterForm;
use App\Livewire\QuestionPractice;
use App\Mail\ConfirmSubscription;
use App\Mail\EnquiryReceived;
use App\Models\AnalyticsEvent;
use App\Models\ClinicalCase;
use App\Models\Enquiry;
use App\Models\NotFoundLog;
use App\Models\PageView;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\Redirect;
use App\Models\Subscriber;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RolesSeeder;
use Database\Seeders\SynonymSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('creates a 301 redirect when a published slug changes', function () {
    $case = ClinicalCase::factory()->create(['slug' => 'old-name']);
    $case->update(['slug' => 'new-name']);

    expect(Redirect::where('source_path', '/cases/old-name')->value('target_url'))->toBe('/cases/new-name');
    $this->get('/cases/old-name')->assertRedirect('/cases/new-name')->assertStatus(301);
});

it('logs unknown URLs in the 404 monitor but ignores scanner noise', function () {
    $this->get('/does-not-exist-anywhere')->assertNotFound();
    $this->get('/wp-login.php')->assertNotFound();

    expect(NotFoundLog::pluck('path')->all())->toBe(['/does-not-exist-anywhere']);
});

it('expands medical abbreviations in search', function () {
    $this->seed(SynonymSeeder::class);
    ClinicalCase::factory()->create(['title' => 'Central serous chorioretinopathy after steroid cream']);

    $this->get('/search?q=CSR')->assertOk()->assertSee('Central serous chorioretinopathy after steroid cream');
    $this->getJson('/search/suggest?q=csr')->assertOk()->assertJsonPath('results.0.type', 'Case');
});

it('subscribes with double opt-in and unsubscribes with one click', function () {
    Mail::fake();

    Livewire::test(NewsletterForm::class)->set('email', 'resident@example.org')->call('subscribe')->assertSet('result', 'pending');

    $subscriber = Subscriber::where('email', 'resident@example.org')->firstOrFail();
    expect($subscriber->status->value)->toBe('pending');
    Mail::assertSent(ConfirmSubscription::class);

    $this->get(route('newsletter.confirm', $subscriber->token))->assertOk()->assertSee('You’re subscribed');
    expect($subscriber->fresh()->status->value)->toBe('subscribed');

    $this->post(route('newsletter.unsubscribe.confirm', $subscriber->token))->assertOk();
    expect($subscriber->fresh()->status->value)->toBe('unsubscribed');
});

it('stores contact enquiries, notifies the owner and blocks bots', function () {
    Mail::fake();
    Settings::set('site.notification_email', 'owner@example.org');

    Livewire::test(ContactForm::class)
        ->set('renderedAt', time() - 10)
        ->set('name', 'Dr Jones')->set('email', 'jones@example.org')->set('subject', 'Grand round invitation')
        ->set('message', 'We would like to invite you to speak at our regional meeting next spring.')->set('consent', true)
        ->call('submit')->assertSet('sent', true);

    expect(Enquiry::count())->toBe(1);
    Mail::assertSent(EnquiryReceived::class);

    Livewire::test(ContactForm::class)->set('website', 'spam')->set('renderedAt', time() - 10)->call('submit')->assertSet('sent', true);
    expect(Enquiry::count())->toBe(1);
});

it('validates the contact form', function () {
    Livewire::test(ContactForm::class)->set('renderedAt', time() - 10)->call('submit')->assertHasErrors(['name', 'email', 'subject', 'message', 'consent']);
});

it('records answers in the question bank', function () {
    $question = Question::factory()->create();

    Livewire::test(QuestionPractice::class, ['questionId' => $question->id])
        ->call('choose', 'A')->call('submit')->assertSet('answered', true)->assertSee('Correct!');

    expect(QuestionAttempt::first()->is_correct)->toBeTrue()
        ->and($question->fresh()->attempts_count)->toBe(1)
        ->and($question->fresh()->correct_count)->toBe(1);
});

it('collects cookieless page views and events, ignoring bots', function () {
    $case = ClinicalCase::factory()->create();
    $ua = ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 Version/17.0 Safari/605.1.15'];

    $this->postJson('/_a', ['u' => $case->urlPath(), 'r' => 'https://www.google.com/', 's' => ['type' => 'case', 'id' => $case->id], 'w' => 1400], $ua)->assertNoContent();
    $this->postJson('/_e', ['n' => 'case_guess', 'p' => ['correct' => true], 's' => ['type' => 'case', 'id' => $case->id]], $ua)->assertNoContent();
    $this->postJson('/_e', ['n' => 'case_reveal', 's' => ['type' => 'case', 'id' => $case->id]], $ua)->assertNoContent();
    $this->postJson('/_a', ['u' => '/'], ['HTTP_USER_AGENT' => 'Googlebot/2.1'])->assertNoContent();
    $this->postJson('/_e', ['n' => 'not_allowed'], $ua);

    $view = PageView::sole();
    expect($view->referrer_host)->toBe('google.com')->and($view->device)->toBe('desktop')->and($view->browser)->toBe('Safari')
        ->and(strlen($view->visitor_hash))->toBe(16)
        ->and($case->fresh()->views_count)->toBe(1)
        ->and($case->fresh()->guesses_count)->toBe(1)
        ->and($case->fresh()->correct_guesses_count)->toBe(1)
        ->and($case->fresh()->reveals_count)->toBe(1)
        ->and(AnalyticsEvent::count())->toBe(2);
});

it('registers members who cannot access the dashboard', function () {
    $this->seed(RolesSeeder::class);

    $this->post('/register', [
        'name' => 'Dr New Member', 'email' => 'member@example.org', 'career_stage' => 'resident',
        'password' => 'Str0ngPassword9', 'password_confirmation' => 'Str0ngPassword9', 'terms' => '1',
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'member@example.org')->firstOrFail();
    expect($user->hasRole('member'))->toBeTrue();
    $this->get('/account')->assertOk()->assertSee('My learning');
    $this->get('/'.config('cms.admin_path'))->assertForbidden();
});

it('sends staff to the secure dashboard login', function () {
    $staff = $this->staff('editor', ['password' => 'Password123', 'email' => 'editor@example.org']);

    $this->post('/login', ['email' => 'editor@example.org', 'password' => 'Password123'])
        ->assertRedirect(url(config('cms.admin_path').'/login'));
    $this->assertGuest();
});

it('lets members export and delete their data', function () {
    $user = User::factory()->create(['password' => 'Password123']);
    $this->actingAs($user)->get('/account/export')->assertOk()->assertDownload('my-data.json');
    $this->actingAs($user)->delete('/account', ['password' => 'Password123'])->assertRedirect('/');
    expect(User::find($user->id))->toBeNull();
});

it('toggles bookmarks', function () {
    $user = User::factory()->create();
    $case = ClinicalCase::factory()->create();

    $this->actingAs($user)->postJson("/account/bookmarks/case/{$case->id}")->assertJson(['saved' => true]);
    expect($user->hasBookmarked($case))->toBeTrue();
    $this->actingAs($user)->postJson("/account/bookmarks/case/{$case->id}")->assertJson(['saved' => false]);
});
