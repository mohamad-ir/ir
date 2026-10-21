<?php

namespace App\Services\Access;

use App\Enums\AccessLevel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarRegistration;
use Illuminate\Database\Eloquent\Model;

/**
 * Single decision point for "who may see this item": public / free account /
 * paying member for library content, and enrolment / registration rules for
 * courses and webinars.
 */
class ContentAccess
{
    public function level(Model $content): AccessLevel
    {
        $level = $content->access_level ?? AccessLevel::Public;

        return $level instanceof AccessLevel ? $level : (AccessLevel::tryFrom((string) $level) ?? AccessLevel::Public);
    }

    public function canView(Model $content, ?User $user): bool
    {
        if ($user?->isStaff()) {
            return true;
        }

        return match ($this->level($content)) {
            AccessLevel::Public => true,
            AccessLevel::Registered => $user !== null,
            AccessLevel::Members => (bool) $user?->isMember(),
        };
    }

    public function reason(Model $content): ?string
    {
        return match ($this->level($content)) {
            AccessLevel::Registered => 'This material is available free to registered members.',
            AccessLevel::Members => 'This material is included with membership.',
            default => null,
        };
    }

    // Courses --------------------------------------------------------------

    public function enrollment(Course $course, ?User $user): ?Enrollment
    {
        return $user ? Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first() : null;
    }

    /** Full access to every lesson of the course. */
    public function canAccessCourse(Course $course, ?User $user, ?Enrollment $enrollment = null): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isStaff()) {
            return true;
        }

        $enrollment ??= $this->enrollment($course, $user);
        if ($enrollment && in_array($enrollment->source, ['purchase', 'free', 'admin'], true)) {
            return true;
        }

        // Membership access lasts as long as the membership does.
        if ($course->included_in_membership && $user->isMember()) {
            return true;
        }

        return $enrollment !== null && $course->isFree();
    }

    public function canViewLesson(Lesson $lesson, ?User $user): bool
    {
        return $lesson->is_preview || $this->canAccessCourse($lesson->course, $user);
    }

    // Webinars -------------------------------------------------------------

    public function registration(Webinar $webinar, ?User $user): ?WebinarRegistration
    {
        return $user ? WebinarRegistration::where('webinar_id', $webinar->id)->where('user_id', $user->id)->whereNull('canceled_at')->first() : null;
    }

    /** Members may register without paying when the webinar is free for members. */
    public function webinarFreeFor(Webinar $webinar, ?User $user): bool
    {
        return $webinar->isFree() || ($webinar->free_for_members && $user?->isMember());
    }

    public function canWatchRecording(Webinar $webinar, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isStaff()
            || $webinar->isFree()
            || $this->registration($webinar, $user) !== null
            || ($webinar->free_for_members && $user->isMember());
    }
}
