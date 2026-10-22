<?php

namespace App\Livewire;

use App\Models\Comment;
use App\Services\Comments\CommentService;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Discussion under an OCT case, article or video: threaded (one level), with likes,
 * reports, editing, pinning and inline moderation for staff with comments.moderate.
 */
class Comments extends Component
{
    #[Locked]
    public string $type;

    #[Locked]
    public int $itemId;

    public string $body = '';

    /** Honeypot: real visitors never see or fill this field. */
    public string $website = '';

    public ?int $replyTo = null;

    public string $replyBody = '';

    public ?int $editing = null;

    public string $editBody = '';

    public ?int $reporting = null;

    public string $reportReason = 'spam';

    public string $sort = 'top';

    public int $perPage = 10;

    public ?string $notice = null;

    #[Locked]
    public string $returnTo = '/';

    public function mount(Model $item): void
    {
        $this->returnTo = '/'.ltrim(request()->path(), '/');
        $this->type = $item->getMorphClass();
        $this->itemId = $item->getKey();
    }

    protected function item(): Model
    {
        $class = Relation::getMorphedModel($this->type) ?? abort(404);

        return $class::findOrFail($this->itemId);
    }

    protected function comment(int $id): Comment
    {
        return Comment::where('commentable_type', $this->type)->where('commentable_id', $this->itemId)->findOrFail($id);
    }

    protected function requireUser(): \App\Models\User
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login', ['redirect' => $this->returnTo]));
        }

        return $user ?? abort(401);
    }

    public function post(CommentService $comments): void
    {
        $user = $this->requireUser();
        if ($this->website !== '') {
            return; // bot
        }
        $this->validate(['body' => ['required', 'string', 'min:2', 'max:'.CommentService::MAX_LENGTH]], [], ['body' => 'comment']);

        $comment = $comments->post($user, $this->item(), $this->body);
        $this->reset('body');
        $this->sort = 'newest';
        $this->notice = $comment->isApproved() ? null : 'Thank you — your comment will appear once a moderator has approved it.';
        $this->dispatch('comment-posted', id: $comment->id);
    }

    public function startReply(int $id): void
    {
        $this->requireUser();
        $this->replyTo = $id;
        $this->replyBody = '';
        $this->resetErrorBag();
    }

    public function reply(CommentService $comments): void
    {
        $user = $this->requireUser();
        $this->validate(['replyBody' => ['required', 'string', 'min:2', 'max:'.CommentService::MAX_LENGTH]], [], ['replyBody' => 'reply']);
        $parent = $this->comment($this->replyTo ?? 0);
        abort_unless($parent->isApproved(), 404);

        try {
            $comment = $comments->post($user, $this->item(), $this->replyBody, $parent);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages(['replyBody' => $e->errors()['body'] ?? $e->getMessage()]);
        }
        $this->reset('replyTo', 'replyBody');
        $this->notice = $comment->isApproved() ? null : 'Thank you — your reply will appear once a moderator has approved it.';
    }

    public function startEdit(int $id): void
    {
        $comment = $this->comment($id);
        abort_unless($this->canEdit($comment), 403);
        $this->editing = $id;
        $this->editBody = $comment->body;
    }

    public function saveEdit(CommentService $comments): void
    {
        $comments->edit($this->comment($this->editing ?? 0), $this->requireUser(), $this->editBody);
        $this->reset('editing', 'editBody');
    }

    public function delete(int $id, CommentService $comments): void
    {
        $comments->delete($this->comment($id), $this->requireUser());
    }

    public function like(int $id, CommentService $comments): void
    {
        $comment = $this->comment($id);
        abort_unless($comment->isApproved(), 404);
        $comments->toggleLike($comment, $this->requireUser());
    }

    public function startReport(int $id): void
    {
        $this->requireUser();
        $this->reporting = $id;
        $this->reportReason = 'spam';
    }

    public function report(CommentService $comments): void
    {
        $comment = $this->comment($this->reporting ?? 0);
        $comments->report($comment, $this->requireUser(), $this->reportReason);
        $this->reset('reporting');
        $this->notice = 'Thank you for the report — a moderator will review this comment.';
    }

    // Moderation --------------------------------------------------------------

    protected function moderator(): \App\Models\User
    {
        $user = $this->requireUser();
        abort_unless($user->can('comments.moderate'), 403);

        return $user;
    }

    public function togglePin(int $id): void
    {
        $this->moderator();
        $comment = $this->comment($id);
        $comment->forceFill(['is_pinned' => ! $comment->is_pinned])->saveQuietly();
    }

    public function hide(int $id, CommentService $comments): void
    {
        $this->moderator();
        $comments->setStatus($this->comment($id), 'rejected');
    }

    public function approve(int $id, CommentService $comments): void
    {
        $comments->approve($this->comment($id), $this->moderator());
    }

    public function toggleComments(): void
    {
        $this->moderator();
        $item = $this->item();
        $item->forceFill(['comments_enabled' => ! $item->comments_enabled])->saveQuietly();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function setSort(string $sort): void
    {
        $this->sort = in_array($sort, ['top', 'newest', 'oldest'], true) ? $sort : 'top';
        $this->perPage = 10;
    }

    public function canEdit(Comment $comment): bool
    {
        $user = auth()->user();

        return $user && $comment->user_id === $user->id
            && ($user->isStaff() || $comment->created_at->gt(now()->subMinutes((int) Settings::get('comments.edit_minutes', 15))));
    }

    public function render()
    {
        $item = $this->item();
        $user = auth()->user();
        $moderator = $user?->can('comments.moderate') ?? false;

        $visible = fn ($q) => $moderator
            ? $q->whereIn('status', ['approved', 'pending'])
            : $q->visibleTo($user);

        $query = Comment::where('commentable_type', $this->type)->where('commentable_id', $this->itemId)
            ->whereNull('parent_id')
            ->where(fn ($q) => $visible($q)->orWhereHas('replies', fn ($r) => $r->where('status', 'approved')))
            ->with(['user.roles', 'replies' => fn ($q) => $visible($q)->with('user.roles')])
            ->orderByDesc('is_pinned');

        match ($this->sort) {
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            default => $query->orderByDesc('likes_count')->latest(),
        };

        $total = (clone $query)->count();
        $threads = $query->limit($this->perPage)->get();

        $ids = $threads->pluck('id')->merge($threads->flatMap->replies->pluck('id'));
        $liked = $user ? DB::table('comment_likes')->where('user_id', $user->id)->whereIn('comment_id', $ids)->pluck('comment_id')->all() : [];
        $reported = $user ? DB::table('comment_reports')->where('user_id', $user->id)->whereIn('comment_id', $ids)->pluck('comment_id')->all() : [];

        return view('livewire.comments', [
            'item' => $item,
            'threads' => $threads,
            'total' => $total,
            'count' => $item->comments_count,
            'open' => $item->commentsOpen() && app(\App\Services\Access\ContentAccess::class)->canView($item, $user),
            'moderator' => $moderator,
            'liked' => $liked,
            'reported' => $reported,
            'needsVerification' => $user && ! $user->hasVerifiedEmail() && ! $user->isStaff() && Settings::get('comments.require_verified', true),
            'guidelines' => Settings::get('comments.guidelines'),
            'authorId' => $item->author_id ?? null,
        ]);
    }
}
