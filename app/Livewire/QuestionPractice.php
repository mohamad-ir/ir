<?php

namespace App\Livewire;

use App\Enums\Exam;
use App\Enums\QuestionType;
use App\Models\AnalyticsEvent;
use App\Models\ExamTopic;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Services\Access\ContentAccess;
use App\Services\Analytics\VisitorHash;
use App\Support\RichText;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/** Question-bank practice: one question at a time with instant feedback and explanations. */
class QuestionPractice extends Component
{
    #[Url(except: '')]
    public string $topic = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $exam = '';

    #[Url(as: 'question', except: null)]
    public ?int $questionId = null;

    public ?string $selected = null;

    public bool $answered = false;

    public bool $revealed = false;

    /** @var array<int, int> */
    public array $seen = [];

    public int $sessionCorrect = 0;

    public int $sessionAnswered = 0;

    public function mount(): void
    {
        if (! $this->questionId || ! $this->baseQuery()->whereKey($this->questionId)->exists()) {
            $this->questionId = $this->pickNext();
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['topic', 'type', 'exam'], true)) {
            $this->seen = [];
            $this->questionId = $this->pickNext();
            $this->resetAnswer();
        }
    }

    protected function baseQuery()
    {
        return Question::query()->published()
            ->when($this->topic !== '', fn ($q) => $q->whereHas('examTopic', fn ($t) => $t->where('slug', $this->topic)))
            ->when($this->type !== '' && QuestionType::tryFrom($this->type), fn ($q) => $q->where('type', $this->type))
            ->when($this->exam !== '' && Exam::tryFrom($this->exam), fn ($q) => $q->whereIn('exam', [$this->exam, Exam::All->value]));
    }

    protected function pickNext(): ?int
    {
        return $this->baseQuery()->whereNotIn('id', $this->seen ?: [0])->inRandomOrder()->value('id')
            ?? $this->baseQuery()->inRandomOrder()->value('id');
    }

    protected function resetAnswer(): void
    {
        $this->selected = null;
        $this->answered = false;
        $this->revealed = false;
    }

    public function choose(string $key): void
    {
        if (! $this->answered) {
            $this->selected = $key;
        }
    }

    public function submit(): void
    {
        $question = $this->current();
        if (! $question || $this->answered || $this->selected === null || ! $this->canView($question)) {
            return;
        }

        $correct = $question->correctKey() === $this->selected;
        $this->record($question, $this->selected, $correct);
        $this->answered = true;
        $this->revealed = true;
        $this->sessionAnswered++;
        $this->sessionCorrect += $correct ? 1 : 0;
    }

    public function reveal(): void
    {
        $question = $this->current();
        if (! $question || ! $this->canView($question)) {
            return;
        }
        if (! $this->revealed && ! $question->isMultipleChoice()) {
            $this->record($question, null, null);
            $this->sessionAnswered++;
        }
        $this->revealed = true;
        $this->answered = true;
    }

    public function next(): void
    {
        if ($this->questionId) {
            $this->seen[] = $this->questionId;
        }
        $this->questionId = $this->pickNext();
        $this->resetAnswer();
    }

    protected function record(Question $question, ?string $key, ?bool $correct): void
    {
        QuestionAttempt::create([
            'question_id' => $question->id,
            'user_id' => auth()->id(),
            'visitor_hash' => app(VisitorHash::class)->for(request()),
            'selected_key' => $key,
            'is_correct' => $correct,
        ]);

        Question::whereKey($question->id)->toBase()->increment('attempts_count', 1, $correct ? ['correct_count' => DB::raw('correct_count + 1')] : []);

        AnalyticsEvent::create([
            'name' => 'question_answer',
            'subject_type' => 'question',
            'subject_id' => $question->id,
            'visitor_hash' => app(VisitorHash::class)->for(request()),
            'properties' => ['correct' => $correct === null ? 'n/a' : ($correct ? '1' : '0')],
            'created_at' => now(),
        ]);
    }

    protected function current(): ?Question
    {
        return $this->questionId ? Question::published()->with(['media', 'examTopic'])->find($this->questionId) : null;
    }

    protected function canView(Question $question): bool
    {
        return app(ContentAccess::class)->canView($question, auth()->user());
    }

    public function render()
    {
        $question = $this->current();

        return view('livewire.question-practice', [
            'question' => $question,
            'canView' => $question ? $this->canView($question) : true,
            'stem' => $question ? RichText::render($question->stem) : null,
            'explanation' => $question ? RichText::render($question->explanation, $question->references ?? []) : null,
            'modelAnswer' => $question ? RichText::render($question->model_answer) : null,
            'topics' => ExamTopic::published()->orderBy('sort_order')->get(['title', 'slug']),
            'types' => QuestionType::cases(),
            'exams' => collect(Exam::cases())->reject(fn ($e) => $e === Exam::All),
            'available' => $this->baseQuery()->count(),
        ]);
    }
}
