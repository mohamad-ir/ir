<?php

namespace App\Models;

use App\Enums\Exam;
use App\Models\Concerns\CreatesSlugRedirects;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Searchable;
use App\Support\Html;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** FRCS / FRCOphth syllabus topic with revision notes and exam tips. */
class ExamTopic extends Model
{
    use CreatesSlugRedirects, HasPublishing, HasRevisions, HasSeo, LogsActivity, Searchable;

    protected $fillable = ['title', 'slug', 'exam', 'summary', 'body', 'icon', 'sort_order', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['exam' => Exam::class];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function pathForSlug(string $slug): string
    {
        return '/exam-prep/'.$slug;
    }

    public function toSearchDocument(): ?array
    {
        if ($this->status?->value !== 'published') {
            return null;
        }

        return [
            'type' => 'exam_topic',
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => Html::text($this->body),
            'keywords' => $this->exam?->getLabel().', FRCOphth, FRCS, exam preparation',
            'url' => $this->urlPath(),
            'published_at' => $this->published_at,
        ];
    }
}
