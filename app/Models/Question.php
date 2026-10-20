<?php

namespace App\Models;

use App\Enums\AccessLevel;
use App\Enums\Difficulty;
use App\Enums\Exam;
use App\Enums\QuestionType;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasTaxonomies;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Searchable;
use App\Support\Html;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Exam question bank item (SBA/MCQ, image-based, viva, clinical case). */
class Question extends Model
{
    use HasFactory, HasPublishing, HasRevisions, HasTaxonomies, LogsActivity, Searchable, SoftDeletes;

    protected $fillable = [
        'exam_topic_id', 'type', 'exam', 'title', 'stem', 'media_id', 'options', 'explanation', 'model_answer',
        'key_points', 'references', 'difficulty', 'access_level', 'status', 'published_at', 'author_id',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'key_points' => 'array',
            'references' => 'array',
            'type' => QuestionType::class,
            'exam' => Exam::class,
            'difficulty' => Difficulty::class,
            'access_level' => AccessLevel::class,
        ];
    }

    public function examTopic(): BelongsTo
    {
        return $this->belongsTo(ExamTopic::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }

    public function isMultipleChoice(): bool
    {
        return collect($this->options ?? [])->filter(fn ($o) => filled($o['text'] ?? null))->count() >= 2;
    }

    /** Options keyed A, B, C… */
    public function lettered(): array
    {
        return collect($this->options ?? [])->filter(fn ($o) => filled($o['text'] ?? null))->values()
            ->mapWithKeys(fn ($o, $i) => [chr(65 + $i) => $o])->all();
    }

    public function correctKey(): ?string
    {
        foreach ($this->lettered() as $key => $option) {
            if (! empty($option['is_correct'])) {
                return $key;
            }
        }

        return null;
    }

    public function successRate(): ?int
    {
        return $this->attempts_count ? (int) round($this->correct_count / $this->attempts_count * 100) : null;
    }

    public function url(): string
    {
        return route('exam.practice', ['question' => $this->id]);
    }

    public function toSearchDocument(): ?array
    {
        if ($this->status?->value !== 'published' || $this->trashed()) {
            return null;
        }

        return [
            'type' => 'question',
            'title' => $this->title,
            'summary' => str(Html::text($this->stem))->limit(220)->toString(),
            'content' => Html::text($this->stem)."\n".Html::text($this->explanation),
            'keywords' => $this->topics->map->label()->push($this->type?->getLabel(), $this->examTopic?->title)->filter()->implode(', '),
            'url' => '/exam-prep/practice?question='.$this->id,
            'image_url' => $this->media?->thumbUrl(),
            'published_at' => $this->published_at,
        ];
    }
}
