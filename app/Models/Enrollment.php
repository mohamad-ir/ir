<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    public const SOURCES = ['purchase' => 'Purchased', 'membership' => 'Membership', 'free' => 'Free course', 'admin' => 'Granted by staff'];

    protected $fillable = ['user_id', 'course_id', 'source', 'order_id', 'last_lesson_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(fn (Enrollment $e) => Course::whereKey($e->course_id)->increment('enrollments_count'));
        static::deleted(fn (Enrollment $e) => Course::whereKey($e->course_id)->where('enrollments_count', '>', 0)->decrement('enrollments_count'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lastLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'last_lesson_id');
    }
}
