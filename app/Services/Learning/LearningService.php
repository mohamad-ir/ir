<?php

namespace App\Services\Learning;

use App\Mail\CourseEnrolled;
use App\Mail\WebinarRegistered;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarRegistration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Grants and revokes course enrolments and webinar seats, and tracks lesson progress. */
class LearningService
{
    public function enroll(User $user, Course $course, string $source, ?Order $order = null, bool $notify = true): Enrollment
    {
        $enrollment = Enrollment::firstOrNew(['user_id' => $user->id, 'course_id' => $course->id]);
        $isNew = ! $enrollment->exists;

        // A purchase or a staff grant upgrades a membership enrolment to permanent access.
        if ($isNew || ($enrollment->source === 'membership' && in_array($source, ['purchase', 'admin'], true))) {
            $enrollment->source = $source;
        }
        if ($order) {
            $enrollment->order_id = $order->id;
        }
        $enrollment->save();

        if ($isNew && $notify && in_array($source, ['purchase', 'admin', 'free'], true)) {
            $this->mail($user, new CourseEnrolled($enrollment));
        }

        return $enrollment;
    }

    public function revokeEnrollment(Order $order): void
    {
        Enrollment::where('order_id', $order->id)->where('source', 'purchase')->get()->each->delete();
    }

    public function register(User $user, Webinar $webinar, string $source, ?Order $order = null, bool $notify = true): WebinarRegistration
    {
        $registration = WebinarRegistration::firstOrNew(['webinar_id' => $webinar->id, 'user_id' => $user->id]);
        $wasActive = $registration->exists && $registration->canceled_at === null;

        if (! $wasActive) {
            $registration->source = $source;
            $registration->canceled_at = null;
        } elseif ($registration->source === 'membership' && $source === 'purchase') {
            $registration->source = $source;
        }
        if ($order) {
            $registration->order_id = $order->id;
        }
        $registration->save();

        if (! $wasActive && $notify) {
            $this->mail($user, new WebinarRegistered($registration));
        }

        return $registration;
    }

    public function revokeRegistration(Order $order): void
    {
        WebinarRegistration::where('order_id', $order->id)->whereNull('canceled_at')->get()
            ->each(fn (WebinarRegistration $r) => $r->update(['canceled_at' => now()]));
    }

    /** Marks a lesson complete. A lesson with a quiz is complete only once the quiz has been passed. */
    public function completeLesson(User $user, Lesson $lesson): bool
    {
        if ($lesson->hasQuiz() && ! $lesson->passedBy($user)) {
            return false;
        }

        LessonProgress::firstOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['course_id' => $lesson->course_id, 'completed_at' => now()],
        );

        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $lesson->course_id)->first();
        if ($enrollment && ! $enrollment->completed_at && $this->progress($user, $lesson->course) >= 100) {
            $enrollment->update(['completed_at' => now()]);
            $this->courseCompleted($user, $lesson->course, $enrollment);
        }

        return true;
    }

    /** Hook for course completion: issues the certificate. */
    protected function courseCompleted(User $user, Course $course, Enrollment $enrollment): void
    {
        app(CertificateService::class)->issue($user, $course);
    }

    public function uncompleteLesson(User $user, Lesson $lesson): void
    {
        LessonProgress::where('user_id', $user->id)->where('lesson_id', $lesson->id)->delete();
        Enrollment::where('user_id', $user->id)->where('course_id', $lesson->course_id)->update(['completed_at' => null]);
    }

    /** @return array<int, int> lesson ids the user has completed in a course */
    public function completedLessonIds(?User $user, Course $course): array
    {
        return $user ? LessonProgress::where('user_id', $user->id)->where('course_id', $course->id)->pluck('lesson_id')->all() : [];
    }

    public function progress(?User $user, Course $course): int
    {
        $total = $course->lessons()->count();
        if (! $user || ! $total) {
            return 0;
        }
        $done = LessonProgress::where('user_id', $user->id)->where('course_id', $course->id)
            ->whereIn('lesson_id', $course->lessons()->select('id'))->count();

        return (int) floor($done / $total * 100);
    }

    /** Queues an email; failures never break a payment or registration. */
    public function mail(User $user, \Illuminate\Mail\Mailable $mailable): void
    {
        try {
            Mail::to($user->email, $user->name)->queue($mailable);
        } catch (Throwable $e) {
            Log::warning('Could not queue learning email: '.$e->getMessage());
        }
    }
}
