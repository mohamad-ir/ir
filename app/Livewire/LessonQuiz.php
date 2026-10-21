<?php

namespace App\Livewire;

use App\Models\Lesson;
use App\Models\LessonQuizAttempt;
use App\Services\Access\ContentAccess;
use App\Services\Learning\LearningService;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * End-of-lesson quiz. Questions are rendered on the server; which answers are correct
 * is never sent to the browser before the learner submits.
 */
class LessonQuiz extends Component
{
    #[Locked]
    public int $lessonId;

    /** @var array<int, array<int, int|string>> question index => selected option indexes */
    public array $answers = [];

    #[Locked]
    public ?string $nextUrl = null;

    public bool $submitted = false;

    public ?int $score = null;

    public bool $passed = false;

    /** @var array<int, bool> */
    public array $results = [];

    public function mount(int $lessonId, ?string $nextUrl = null): void
    {
        $this->lessonId = $lessonId;
        $this->nextUrl = $nextUrl;
    }

    protected function lesson(): Lesson
    {
        return Lesson::with('course')->findOrFail($this->lessonId);
    }

    public function choose(int $question, int $option, bool $multiple): void
    {
        if ($this->submitted) {
            return;
        }
        $current = array_map('intval', (array) ($this->answers[$question] ?? []));
        $this->answers[$question] = $multiple
            ? (in_array($option, $current, true) ? array_values(array_diff($current, [$option])) : [...$current, $option])
            : [$option];
    }

    public function submit(LearningService $learning, ContentAccess $access): void
    {
        $lesson = $this->lesson();
        $user = auth()->user();
        abort_unless($user && $access->canAccessCourse($lesson->course, $user), 403);

        $questions = $lesson->quizQuestions();
        $unanswered = collect($questions)->keys()->reject(fn ($i) => ! empty($this->answers[$i]))->count();
        if ($unanswered) {
            $this->addError('answers', $unanswered === 1 ? 'Please answer the remaining question.' : "Please answer the remaining {$unanswered} questions.");

            return;
        }

        $grade = $lesson->gradeQuiz($this->answers);
        LessonQuizAttempt::create([
            'user_id' => $user->id, 'lesson_id' => $lesson->id, 'course_id' => $lesson->course_id,
            'score' => $grade['score'], 'passed' => $grade['passed'], 'answers' => $this->answers,
        ]);

        $this->submitted = true;
        $this->score = $grade['score'];
        $this->passed = $grade['passed'];
        $this->results = $grade['results'];

        if ($grade['passed']) {
            $learning->completeLesson($user, $lesson);
            $this->dispatch('lesson-completed');
        }
    }

    public function retry(): void
    {
        $this->reset(['answers', 'submitted', 'score', 'passed', 'results']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $lesson = $this->lesson();
        $user = auth()->user();

        return view('livewire.lesson-quiz', [
            'lesson' => $lesson,
            'questions' => $lesson->quizQuestions(),
            'best' => $user ? LessonQuizAttempt::where('user_id', $user->id)->where('lesson_id', $lesson->id)->max('score') : null,
            'alreadyPassed' => $lesson->passedBy($user),
        ]);
    }
}
