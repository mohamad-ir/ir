<?php

namespace App\Models;

use App\Enums\VideoProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'course_section_id', 'title', 'slug', 'summary', 'body', 'video_provider', 'video_url',
        'provider_video_id', 'video_media_id', 'duration_minutes', 'attachments', 'quiz', 'is_preview', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'video_provider' => VideoProvider::class,
            'attachments' => 'array',
            'quiz' => 'array',
            'is_preview' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Lesson $lesson) {
            if (blank($lesson->slug)) {
                $lesson->slug = str($lesson->title)->slug()->limit(80, '')->toString() ?: 'lesson';
            }
            if ($lesson->isDirty('video_url') && filled($lesson->video_url) && in_array($lesson->video_provider, [VideoProvider::Youtube, VideoProvider::Vimeo], true)) {
                $lesson->provider_video_id = Video::parseProviderId($lesson->video_provider, $lesson->video_url);
            }
        });
        static::saved(fn (Lesson $lesson) => $lesson->course?->touch());
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function videoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'video_media_id');
    }

    public function hasVideo(): bool
    {
        return $this->video_provider !== null && $this->embedUrl() !== null;
    }

    public function embedUrl(): ?string
    {
        return match ($this->video_provider) {
            VideoProvider::Youtube => $this->provider_video_id ? "https://www.youtube-nocookie.com/embed/{$this->provider_video_id}?rel=0&modestbranding=1" : null,
            VideoProvider::Vimeo => $this->provider_video_id ? $this->vimeoEmbedUrl() : null,
            VideoProvider::File => $this->videoMedia?->url(),
            VideoProvider::Url => $this->video_url,
            default => null,
        };
    }

    protected function vimeoEmbedUrl(): string
    {
        [$id, $hash] = array_pad(explode(':', $this->provider_video_id, 2), 2, null);

        return "https://player.vimeo.com/video/{$id}?".http_build_query(array_filter(['h' => $hash, 'dnt' => 1]));
    }

    public function isFilePlayer(): bool
    {
        return in_array($this->video_provider, [VideoProvider::File, VideoProvider::Url], true);
    }

    /** @return \Illuminate\Support\Collection<int, Media> */
    public function attachmentMedia()
    {
        $ids = collect($this->attachments ?? [])->filter()->values();

        return $ids->isEmpty() ? collect() : Media::whereIn('id', $ids)->get()->sortBy(fn ($m) => $ids->search($m->id))->values();
    }

    // Quiz -----------------------------------------------------------------

    /** @return array<int, array{question: string, options: array<int, array{text: string, correct: bool}>, explanation: ?string}> */
    public function quizQuestions(): array
    {
        return collect($this->quiz['questions'] ?? [])
            ->map(fn ($q) => [
                'question' => trim((string) ($q['question'] ?? '')),
                'options' => collect($q['options'] ?? [])->filter(fn ($o) => filled($o['text'] ?? null))
                    ->map(fn ($o) => ['text' => (string) $o['text'], 'correct' => (bool) ($o['correct'] ?? false)])->values()->all(),
                'explanation' => $q['explanation'] ?? null,
            ])
            ->filter(fn ($q) => $q['question'] !== '' && count($q['options']) >= 2 && collect($q['options'])->contains('correct', true))
            ->values()->all();
    }

    public function hasQuiz(): bool
    {
        return count($this->quizQuestions()) > 0;
    }

    public function passMark(): int
    {
        return max(1, min(100, (int) ($this->quiz['pass_mark'] ?? 70)));
    }

    /**
     * Grades answers ([question index => [option indexes]]). A question counts as correct only
     * when exactly the correct options are chosen, so "select all that apply" questions work too.
     *
     * @return array{score: int, passed: bool, results: array<int, bool>}
     */
    public function gradeQuiz(array $answers): array
    {
        $questions = $this->quizQuestions();
        $results = [];
        foreach ($questions as $i => $q) {
            $correct = collect($q['options'])->keys()->filter(fn ($k) => $q['options'][$k]['correct'])->values()->sort()->values()->all();
            $given = collect((array) ($answers[$i] ?? []))->map(fn ($v) => (int) $v)->unique()->sort()->values()->all();
            $results[$i] = $given === $correct;
        }
        $score = $questions ? (int) round(collect($results)->filter()->count() / count($questions) * 100) : 0;

        return ['score' => $score, 'passed' => $score >= $this->passMark(), 'results' => $results];
    }

    public function passedBy(?User $user): bool
    {
        return $user && LessonQuizAttempt::where('user_id', $user->id)->where('lesson_id', $this->id)->where('passed', true)->exists();
    }

    public function url(): string
    {
        return route('courses.lesson', [$this->course->slug, $this->slug]);
    }
}
