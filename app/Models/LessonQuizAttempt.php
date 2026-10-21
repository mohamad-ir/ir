<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonQuizAttempt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'lesson_id', 'course_id', 'score', 'passed', 'answers'];

    protected function casts(): array
    {
        return ['passed' => 'boolean', 'answers' => 'array', 'score' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
