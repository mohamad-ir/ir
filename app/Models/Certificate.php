<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Certificate of completion. Name and course title are stored as issued, so later edits do not change it. */
class Certificate extends Model
{
    protected $fillable = ['code', 'user_id', 'course_id', 'recipient_name', 'course_title', 'hours', 'issued_at', 'revoked_at', 'revoke_reason'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'revoked_at' => 'datetime', 'hours' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }

    public function verifyUrl(): string
    {
        return route('certificates.show', $this->code);
    }

    public function downloadUrl(): string
    {
        return route('certificates.download', $this->code);
    }

    public function hoursLabel(): ?string
    {
        if (! $this->hours) {
            return null;
        }

        return rtrim(rtrim(number_format($this->hours, 1), '0'), '.').' '.($this->hours == 1 ? 'hour' : 'hours');
    }
}
