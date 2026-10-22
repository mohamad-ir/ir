<?php

namespace App\Services\Comments;

use App\Mail\CommentAwaitingModeration;
use App\Mail\CommentReply;
use App\Models\Comment;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Posting and moderating comments. Moderation modes:
 *  - "first": a member’s first comment is held for approval, later ones publish immediately (default)
 *  - "all":   every comment is held for approval
 *  - "none":  comments publish immediately (spam checks still apply)
 */
class CommentService
{
    public const MAX_LENGTH = 3000;

    public function post(User $user, Model $commentable, string $body, ?Comment $parent = null): Comment
    {
        $body = trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", $body)));

        if (! $commentable->commentsOpen()) {
            throw ValidationException::withMessages(['body' => 'Comments are closed for this page.']);
        }
        if (Settings::get('comments.require_verified', true) && ! $user->hasVerifiedEmail() && ! $user->isStaff()) {
            throw ValidationException::withMessages(['body' => 'Please confirm your email address before commenting — check your inbox for the link.']);
        }
        if (mb_strlen($body) < 2 || mb_strlen($body) > self::MAX_LENGTH) {
            throw ValidationException::withMessages(['body' => 'Comments must be between 2 and '.number_format(self::MAX_LENGTH).' characters.']);
        }
        if ($parent && ($parent->commentable_type !== $commentable->getMorphClass() || (int) $parent->commentable_id !== (int) $commentable->getKey())) {
            throw ValidationException::withMessages(['body' => 'You can only reply within the same discussion.']);
        }
        // One level of replies keeps discussions readable: replying to a reply joins its thread.
        $parent = $parent?->parent_id ? $parent->parent : $parent;

        $key = 'comment:'.$user->id;
        if (! $user->isStaff() && (RateLimiter::tooManyAttempts($key.':min', 4) || RateLimiter::tooManyAttempts($key.':day', 40))) {
            throw ValidationException::withMessages(['body' => 'You are commenting very quickly — please wait a moment and try again.']);
        }
        if (Comment::where('user_id', $user->id)->where('body', $body)->where('created_at', '>', now()->subDay())->exists()) {
            throw ValidationException::withMessages(['body' => 'You have already posted this comment.']);
        }
        RateLimiter::hit($key.':min', 60);
        RateLimiter::hit($key.':day', 86400);

        [$status, $note] = $this->decideStatus($user, $body);

        $comment = DB::transaction(fn () => Comment::create([
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
            'body' => $body,
            'status' => $status,
            'moderation_note' => $note,
            'ip_hash' => request() ? hash('sha256', request()->ip().config('app.key')) : null,
            'approved_at' => $status === 'approved' ? now() : null,
        ]));

        if ($status === 'approved') {
            $this->afterApproval($comment);
        } elseif ($status === 'pending') {
            $this->notifyModerators($comment);
        }

        return $comment;
    }

    /** @return array{0: string, 1: ?string} status and the reason it was held */
    protected function decideStatus(User $user, string $body): array
    {
        if ($user->isStaff()) {
            return ['approved', null];
        }

        $links = preg_match_all('~https?://~i', $body);
        if ($links > (int) Settings::get('comments.max_links', 2)) {
            return ['pending', 'Contains '.$links.' links'];
        }

        $blocked = collect(preg_split('/[\r\n,]+/', (string) Settings::get('comments.blocked_words', '')))->map(fn ($w) => trim($w))->filter();
        foreach ($blocked as $word) {
            if (mb_stripos($body, $word) !== false) {
                return ['pending', 'Contains a blocked word: '.$word];
            }
        }

        return match (Settings::get('comments.moderation', 'first')) {
            'all' => ['pending', null],
            'none' => ['approved', null],
            default => Comment::where('user_id', $user->id)->where('status', 'approved')->exists() ? ['approved', null] : ['pending', 'First comment from this member'],
        };
    }

    public function approve(Comment $comment, ?User $moderator = null): void
    {
        if ($comment->isApproved()) {
            return;
        }
        $comment->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $moderator?->id, 'moderation_note' => null]);
        $this->afterApproval($comment);
    }

    public function setStatus(Comment $comment, string $status): void
    {
        $wasApproved = $comment->isApproved();
        $comment->update(['status' => $status]);
        if ($wasApproved) {
            $comment->commentable?->refreshCommentsCount();
        }
    }

    public function edit(Comment $comment, User $user, string $body): void
    {
        $minutes = (int) Settings::get('comments.edit_minutes', 15);
        abort_unless($comment->user_id === $user->id && ($user->isStaff() || $comment->created_at->gt(now()->subMinutes($minutes))), 403);

        $body = trim($body);
        if (mb_strlen($body) < 2 || mb_strlen($body) > self::MAX_LENGTH) {
            throw ValidationException::withMessages(['editBody' => 'Comments must be between 2 and '.number_format(self::MAX_LENGTH).' characters.']);
        }
        $comment->update(['body' => $body, 'edited_at' => now()]);
    }

    public function delete(Comment $comment, User $user): void
    {
        abort_unless($comment->user_id === $user->id || $user->can('comments.moderate'), 403);
        $wasApproved = $comment->isApproved();
        $comment->delete();
        if ($wasApproved) {
            $comment->commentable?->refreshCommentsCount();
        }
    }

    public function toggleLike(Comment $comment, User $user): bool
    {
        $existing = DB::table('comment_likes')->where('comment_id', $comment->id)->where('user_id', $user->id);
        if ($existing->exists()) {
            $existing->delete();
            $liked = false;
        } else {
            DB::table('comment_likes')->insertOrIgnore(['comment_id' => $comment->id, 'user_id' => $user->id, 'created_at' => now()]);
            $liked = true;
        }
        $comment->forceFill(['likes_count' => DB::table('comment_likes')->where('comment_id', $comment->id)->count()])->saveQuietly();

        return $liked;
    }

    /** Three reports from different members hide a comment until a moderator looks at it. */
    public function report(Comment $comment, User $user, string $reason): void
    {
        abort_unless(array_key_exists($reason, Comment::REPORT_REASONS), 422);
        DB::table('comment_reports')->insertOrIgnore(['comment_id' => $comment->id, 'user_id' => $user->id, 'reason' => $reason, 'created_at' => now()]);
        $count = DB::table('comment_reports')->where('comment_id', $comment->id)->count();
        $comment->forceFill(['reports_count' => $count])->saveQuietly();

        if ($count >= 3 && $comment->isApproved()) {
            $comment->forceFill(['status' => 'pending', 'moderation_note' => 'Hidden after '.$count.' reports'])->saveQuietly();
            $comment->commentable?->refreshCommentsCount();
            $this->notifyModerators($comment);
        }
    }

    /** GDPR erasure: comments with replies keep their place in the thread but lose their text and author. */
    public function eraseUser(User $user): void
    {
        foreach (Comment::withTrashed()->where('user_id', $user->id)->withCount('replies')->get() as $comment) {
            $target = $comment->commentable;
            if ($comment->replies_count > 0) {
                $comment->forceFill(['body' => '[This comment was removed when its author deleted their account.]', 'user_id' => null])->saveQuietly();
            } else {
                $comment->forceDelete();
            }
            $target?->refreshCommentsCount();
        }
    }

    protected function afterApproval(Comment $comment): void
    {
        $comment->commentable?->refreshCommentsCount();

        $parentAuthor = $comment->parent?->user;
        if ($parentAuthor && $parentAuthor->id !== $comment->user_id && $parentAuthor->notify_replies) {
            $this->send($parentAuthor->email, new CommentReply($comment));
        }
    }

    protected function notifyModerators(Comment $comment): void
    {
        $to = Settings::get('site.notification_email') ?: Settings::get('site.contact_email');
        if ($to && Settings::get('comments.notify_staff', true)) {
            $this->send($to, new CommentAwaitingModeration($comment));
        }
    }

    protected function send(string $to, \Illuminate\Mail\Mailable $mail): void
    {
        try {
            Mail::to($to)->queue($mail);
        } catch (Throwable $e) {
            Log::warning('Could not queue comment email: '.$e->getMessage());
        }
    }
}
