<?php

use App\Livewire\LessonQuiz;
use App\Mail\CertificateIssued;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Learning\CertificateService;
use App\Services\Learning\LearningService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seedRoles();
    Mail::fake();
});

function quizLesson(Course $course): Lesson
{
    $lesson = $course->lessons()->firstOrFail();
    $lesson->update(['quiz' => ['pass_mark' => 50, 'questions' => [
        ['question' => 'Single answer?', 'options' => [['text' => 'Right', 'correct' => true], ['text' => 'Wrong', 'correct' => false]], 'explanation' => 'Because.'],
        ['question' => 'Select all?', 'options' => [['text' => 'A', 'correct' => true], ['text' => 'B', 'correct' => true], ['text' => 'C', 'correct' => false]]],
    ]]]);

    return $lesson->fresh();
}

it('grades single and multiple answer questions exactly', function () {
    $lesson = quizLesson(Course::factory()->withLessons(1)->create());

    expect($lesson->gradeQuiz([0 => [0], 1 => [0, 1]]))->toMatchArray(['score' => 100, 'passed' => true])
        ->and($lesson->gradeQuiz([0 => [0], 1 => [0]]))->toMatchArray(['score' => 50, 'passed' => true])
        ->and($lesson->gradeQuiz([0 => [1], 1 => [0, 1, 2]]))->toMatchArray(['score' => 0, 'passed' => false]);
});

it('does not send correct answers to the browser before submitting', function () {
    $user = User::factory()->create();
    $course = Course::factory()->free()->withLessons(1, 0)->create();
    $lesson = quizLesson($course);
    app(LearningService::class)->enroll($user, $course, 'free', notify: false);

    $this->actingAs($user)->get(route('courses.lesson', [$course->slug, $lesson->slug]))->assertOk()->assertSee('Lesson quiz')->assertDontSee('Because.')->assertDontSee('"correct"', false);
});

it('requires passing the quiz before the lesson completes, then issues the certificate', function () {
    $user = User::factory()->create(['name' => 'Ava Retina']);
    $course = Course::factory()->free()->withLessons(1, 0)->create(['title' => 'OCT basics', 'cpd_hours' => 3]);
    $lesson = quizLesson($course);
    app(LearningService::class)->enroll($user, $course, 'free', notify: false);

    $this->actingAs($user)->post(route('courses.complete', [$course->slug, $lesson->slug]))->assertSessionHas('error');
    expect(Enrollment::sole()->completed_at)->toBeNull();

    Livewire::actingAs($user)->test(LessonQuiz::class, ['lessonId' => $lesson->id])
        ->call('submit')->assertHasErrors('answers')
        ->call('choose', 0, 1, false)->call('choose', 1, 2, true)->call('submit')->assertSet('passed', false)->assertSee('Because.')
        ->call('retry')->call('choose', 0, 0, false)->call('choose', 1, 0, true)->call('choose', 1, 1, true)->call('submit')
        ->assertSet('score', 100)->assertSet('passed', true);

    $certificate = Certificate::sole();
    expect(Enrollment::sole()->completed_at)->not->toBeNull()->and($certificate->recipient_name)->toBe('Ava Retina')
        ->and($certificate->hours)->toBe(3.0)->and($certificate->code)->toMatch('/^[A-Z]{2}-[2-9A-Z]{4}-[2-9A-Z]{4}$/');
    Mail::assertQueued(CertificateIssued::class);
});

it('verifies certificates publicly and lets only the owner or staff download the PDF', function () {
    $user = User::factory()->create();
    $course = Course::factory()->withLessons(1)->create();
    $certificate = app(CertificateService::class)->issue($user, $course, false);

    $this->get('/certificates/'.$certificate->code)->assertOk()->assertSee('Valid certificate')->assertSee($certificate->recipient_name);
    $this->get('/certificates/'.strtolower($certificate->code))->assertOk();
    $this->get($certificate->downloadUrl())->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get($certificate->downloadUrl())->assertForbidden();

    $pdf = $this->actingAs($user)->get($certificate->downloadUrl())->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($pdf->getContent(), 0, 5))->toBe('%PDF-');

    $certificate->update(['revoked_at' => now(), 'revoke_reason' => 'Error']);
    $this->get('/certificates/'.$certificate->code)->assertSee('revoked');
    $this->actingAs($user)->get($certificate->downloadUrl())->assertForbidden();
});

it('respects courses without certificates and never issues twice', function () {
    $user = User::factory()->create();
    $off = Course::factory()->create(['certificate_enabled' => false]);
    $on = Course::factory()->create();
    $service = app(CertificateService::class);

    expect($service->issue($user, $off, false))->toBeNull()
        ->and($service->issue($user, $on, false)->id)->toBe($service->issue($user, $on, false)->id);
});
