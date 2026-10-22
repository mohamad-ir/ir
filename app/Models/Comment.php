<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Comment extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending' => 'Awaiting approval', 'approved' => 'Approved', 'spam' => 'Spam', 'rejected' => 'Rejected'];

    public const REPORT_REASONS = ['spam' => 'Spam or advertising', 'abuse' => 'Rude or abusive', 'patient' => 'Contains patient-identifiable information', 'misinformation' => 'Misleading medical information', 'other' => 'Something else'];

    protected $fillable = ['commentable_type', 'commentable_id', 'user_id', 'parent_id', 'body', 'status', 'moderation_note', 'is_pinned', 'ip_hash', 'edited_at', 'approved_at', 'approved_by'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean', 'edited_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** Approved comments plus the viewer’s own pending ones (shown with an “awaiting approval” note). */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('status', 'approved')
            ->when($user, fn ($q) => $q->orWhere(fn ($q) => $q->where('user_id', $user->id)->where('status', 'pending'))));
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRemoved(): bool
    {
        return $this->body === \App\Services\Comments\CommentService::REMOVED;
    }

    public function authorName(): string
    {
        return $this->user?->displayName() ?? 'Former member';
    }

    /** Plain text with line breaks and safe, nofollow links. Never renders HTML from the comment. */
    public function bodyHtml(): HtmlString
    {
        $text = e($this->body);
        $text = preg_replace_callback('~\bhttps?://[^\s<]+[^\s<.,;:!?)\]]~i', fn ($m) => '<a href="'.$m[0].'" rel="nofollow ugc noopener" target="_blank" class="link break-all">'.Str::limit($m[0], 60).'</a>', $text);

        return new HtmlString(nl2br($text, false));
    }

    public function excerpt(int $length = 120): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', $this->body), $length);
    }

    public function url(): ?string
    {
        $target = $this->commentable;

        return $target && method_exists($target, 'url') ? $target->url().'#comment-'.$this->id : null;
    }
}
