<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Services\Access\ContentAccess;
use App\Services\Learning\LearningService;
use App\Services\Payments\StripeClient;
use App\Services\Seo\SchemaFactory;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request, SeoManager $seo, LearningService $learning)
    {
        $seo->title('Courses')
            ->description('Structured online courses in OCT interpretation, medical retina, uveitis and exam preparation — learn at your own pace with video lessons and clinical cases.')
            ->breadcrumb('Courses', route('courses.index'));

        $courses = Course::published()->with(['featuredMedia', 'lessons:id,course_id,duration_minutes', 'topics'])
            ->orderByDesc('is_featured')->orderByDesc('published_at')->get();
        $user = $request->user();

        return view('courses.index', [
            'courses' => $courses,
            'enrolled' => $user ? Enrollment::where('user_id', $user->id)->pluck('course_id')->all() : [],
            'isMember' => (bool) $user?->isMember(),
        ]);
    }

    public function show(string $slug, Request $request, SeoManager $seo, ContentAccess $access, LearningService $learning)
    {
        $course = Course::published()->where('slug', $slug)
            ->with(['featuredMedia', 'sections', 'lessons', 'author', 'topics', 'seo.ogImage'])->firstOrFail();

        return $this->render($course, $request, $seo, $access, $learning);
    }

    public function render(Course $course, Request $request, SeoManager $seo, ContentAccess $access, LearningService $learning, bool $preview = false)
    {
        $course->loadMissing(['featuredMedia', 'sections', 'lessons', 'author', 'topics']);
        $user = $request->user();
        $enrollment = $access->enrollment($course, $user);
        $canAccess = $access->canAccessCourse($course, $user, $enrollment);
        $lessons = $course->orderedLessons();
        $completed = $learning->completedLessonIds($user, $course);

        $seo->forModel($course, 'website')
            ->image($course->seo?->ogImage ?? $course->featuredMedia)
            ->breadcrumb('Courses', route('courses.index'))
            ->breadcrumb($course->title, $course->url())
            ->schema(SchemaFactory::course($course));
        if ($preview) {
            $seo->noindex();
        }

        $next = $enrollment?->lastLesson && $lessons->contains('id', $enrollment->last_lesson_id)
            ? $enrollment->lastLesson
            : ($lessons->first(fn ($l) => ! in_array($l->id, $completed, true)) ?? $lessons->first());

        return view('courses.show', [
            'course' => $course,
            'curriculum' => $course->curriculum(),
            'lessonCount' => $lessons->count(),
            'enrollment' => $enrollment,
            'canAccess' => $canAccess,
            'isMember' => (bool) $user?->isMember(),
            'completed' => $completed,
            'progress' => $canAccess ? $learning->progress($user, $course) : 0,
            'nextLesson' => $next,
            'paymentsEnabled' => StripeClient::configured(),
            'certificate' => $user ? \App\Models\Certificate::valid()->where('user_id', $user->id)->where('course_id', $course->id)->first() : null,
            'isPreview' => $preview,
        ]);
    }

    /** Free courses and courses included in membership: one click to start. */
    public function enroll(string $slug, Request $request, ContentAccess $access, LearningService $learning)
    {
        $course = Course::published()->where('slug', $slug)->with('lessons')->firstOrFail();
        $user = $request->user();

        $source = match (true) {
            $course->isFree() => 'free',
            $course->included_in_membership && $user->isMember() => 'membership',
            $user->isStaff() => 'admin',
            default => null,
        };
        abort_if($source === null && ! $access->canAccessCourse($course, $user), 403);

        if (! $access->enrollment($course, $user)) {
            $learning->enroll($user, $course, $source ?? 'admin');
        }

        $first = $course->orderedLessons()->first();

        return $first ? redirect()->route('courses.lesson', [$course->slug, $first->slug]) : redirect()->route('courses.show', $course->slug);
    }

    public function lesson(string $slug, string $lessonSlug, Request $request, SeoManager $seo, ContentAccess $access, LearningService $learning)
    {
        $course = Course::published()->where('slug', $slug)->with(['sections', 'lessons', 'featuredMedia'])->firstOrFail();
        $lesson = $course->lessons->firstWhere('slug', $lessonSlug) ?? abort(404);
        $lesson->setRelation('course', $course);
        $user = $request->user();

        $canView = $access->canViewLesson($lesson, $user);
        $canAccessCourse = $access->canAccessCourse($course, $user);
        $lessons = $course->orderedLessons();
        $index = $lessons->search(fn ($l) => $l->id === $lesson->id);

        if ($canView && $user && $canAccessCourse) {
            $enrollment = $access->enrollment($course, $user);
            if (! $enrollment && ! $user->isStaff()) {
                $enrollment = $learning->enroll($user, $course, $course->isFree() ? 'free' : 'membership', notify: false);
            }
            $enrollment?->update(['last_lesson_id' => $lesson->id]);
        }

        $seo->title($lesson->title.' — '.$course->title)
            ->description($lesson->summary ?: $course->summary)
            ->breadcrumb('Courses', route('courses.index'))
            ->breadcrumb($course->title, $course->url())
            ->breadcrumb($lesson->title, $lesson->url());
        if (! $lesson->is_preview) {
            $seo->noindex();
        }

        return view('courses.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'curriculum' => $course->curriculum(),
            'canView' => $canView,
            'canAccessCourse' => $canAccessCourse,
            'completed' => $learning->completedLessonIds($user, $course),
            'progress' => $canAccessCourse ? $learning->progress($user, $course) : 0,
            'previous' => $index > 0 ? $lessons[$index - 1] : null,
            'next' => $lessons[$index + 1] ?? null,
            'isMember' => (bool) $user?->isMember(),
            'paymentsEnabled' => StripeClient::configured(),
        ]);
    }

    public function complete(string $slug, string $lessonSlug, Request $request, ContentAccess $access, LearningService $learning)
    {
        $course = Course::published()->where('slug', $slug)->with('lessons')->firstOrFail();
        $lesson = $course->lessons->firstWhere('slug', $lessonSlug) ?? abort(404);
        $lesson->setRelation('course', $course);
        abort_unless($access->canAccessCourse($course, $request->user()), 403);

        if ($request->boolean('undo')) {
            $learning->uncompleteLesson($request->user(), $lesson);

            return back();
        }

        $next = $course->orderedLessons()->skipUntil(fn ($l) => $l->id === $lesson->id)->skip(1)->first();
        if (! $learning->completeLesson($request->user(), $lesson)) {
            if ($request->boolean('continue') && $next) {
                return redirect()->route('courses.lesson', [$course->slug, $next->slug]);
            }

            return back()->with('error', 'Pass the lesson quiz to complete this lesson.');
        }

        if ($request->boolean('continue') && $next) {
            return redirect()->route('courses.lesson', [$course->slug, $next->slug]);
        }

        $certificate = $next ? null : \App\Models\Certificate::where('user_id', $request->user()->id)->where('course_id', $course->id)->whereNull('revoked_at')->first();

        return back()->with('status', $next ? 'Lesson completed.' : ($certificate ? 'Course completed — congratulations! Your certificate is ready in My learning.' : 'Course completed — congratulations!'));
    }
}
