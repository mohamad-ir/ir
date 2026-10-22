@php
    $mine = auth()->check() && $comment->user_id === auth()->id();
    $isAuthor = $comment->user_id && $comment->user_id === $authorId;
    $isTeam = $comment->user?->isStaff();
@endphp
<article id="comment-{{ $comment->id }}" @class(['group flex scroll-mt-28 gap-3 sm:gap-4', 'opacity-70' => $comment->status === 'pending']) x-data="{ menu: false }">
    @include('livewire.comments.avatar', ['person' => $comment->user, 'small' => $isReply])
    <div class="min-w-0 flex-1">
        <header class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
            <span class="font-semibold text-ink">{{ $comment->authorName() }}</span>
            @if ($isAuthor)<span class="chip chip-accent !py-0 !text-[11px]">Author</span>
            @elseif ($isTeam)<span class="chip !py-0 !text-[11px]">{{ setting('site.name') }} team</span>@endif
            @if ($comment->user?->career_stage)<span class="text-xs text-muted">{{ $comment->user->career_stage->getLabel() }}</span>@endif
            <span class="text-muted" aria-hidden="true">·</span>
            <a href="#comment-{{ $comment->id }}" class="text-xs text-muted hover:text-ink"><time datetime="{{ $comment->created_at->toIso8601String() }}" title="{{ $comment->created_at->format('j M Y, H:i') }}">{{ $comment->created_at->diffForHumans() }}</time></a>
            @if ($comment->edited_at)<span class="text-xs text-muted">(edited)</span>@endif
            @if ($comment->is_pinned)<span class="flex items-center gap-1 text-xs font-semibold text-accent-ink"><x-heroicon-s-bookmark class="size-3.5" /> Pinned</span>@endif
            @if ($comment->status === 'pending')
                <span class="chip chip-highlight !py-0 !text-[11px]">{{ $mine ? 'Awaiting approval — only you can see this' : 'Awaiting approval' }}</span>
                @if ($moderator && $comment->moderation_note)<span class="text-xs text-muted">{{ $comment->moderation_note }}</span>@endif
            @endif
        </header>

        @if ($editing === $comment->id)
            <form wire:submit="saveEdit" class="mt-2">
                <label for="edit-{{ $comment->id }}" class="sr-only">Edit your comment</label>
                <textarea id="edit-{{ $comment->id }}" wire:model="editBody" rows="3" class="input resize-y" maxlength="{{ \App\Services\Comments\CommentService::MAX_LENGTH }}"></textarea>
                @error('editBody')<p class="field-error">{{ $message }}</p>@enderror
                <div class="mt-2 flex justify-end gap-2">
                    <button type="button" wire:click="$set('editing', null)" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        @else
            <div @class(['mt-1.5 break-words text-[15px] leading-relaxed text-fg', 'italic text-muted' => $comment->isRemoved()])>{!! $comment->bodyHtml() !!}</div>
        @endif

        @unless ($comment->isRemoved() || $editing === $comment->id)
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-medium text-muted">
                @if ($comment->isApproved())
                    @php $hasLiked = in_array($comment->id, $liked); @endphp
                    <button type="button" wire:click="like({{ $comment->id }})" @class(['flex items-center gap-1 transition hover:text-ink', 'text-accent-ink' => $hasLiked]) aria-pressed="{{ $hasLiked ? 'true' : 'false' }}" @guest disabled title="Sign in to mark comments as helpful" @endguest>
                        @if ($hasLiked)<x-heroicon-s-hand-thumb-up class="size-4" />@else<x-heroicon-o-hand-thumb-up class="size-4" />@endif
                        Helpful{{ $comment->likes_count ? ' · '.$comment->likes_count : '' }}
                    </button>
                    @if ($open && auth()->check() && ! $needsVerification)
                        <button type="button" wire:click="startReply({{ $comment->parent_id ?? $comment->id }})" class="flex items-center gap-1 hover:text-ink"><x-heroicon-o-chat-bubble-left class="size-4" /> Reply</button>
                    @endif
                @endif
                @if ($this->canEdit($comment))
                    <button type="button" wire:click="startEdit({{ $comment->id }})" class="hover:text-ink">Edit</button>
                @endif
                @if ($mine || $moderator)
                    <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="Delete this comment?" class="hover:text-red-700">Delete</button>
                @endif
                @auth
                    @if (! $mine && $comment->isApproved())
                        @if (in_array($comment->id, $reported))
                            <span class="flex items-center gap-1"><x-heroicon-s-flag class="size-3.5" /> Reported</span>
                        @else
                            <button type="button" wire:click="startReport({{ $comment->id }})" class="flex items-center gap-1 opacity-70 hover:text-ink hover:opacity-100"><x-heroicon-o-flag class="size-3.5" /> Report</button>
                        @endif
                    @endif
                @endauth
                @if ($moderator)
                    <span class="flex items-center gap-3 border-l border-line pl-3">
                        @if ($comment->status === 'pending')<button type="button" wire:click="approve({{ $comment->id }})" class="text-emerald-700 hover:underline">Approve</button>@endif
                        @if (! $isReply)<button type="button" wire:click="togglePin({{ $comment->id }})" class="hover:text-ink">{{ $comment->is_pinned ? 'Unpin' : 'Pin' }}</button>@endif
                        @if ($comment->isApproved())<button type="button" wire:click="hide({{ $comment->id }})" class="hover:text-red-700">Hide</button>@endif
                    </span>
                @endif
            </div>
        @endunless

        @if ($reporting === $comment->id)
            <form wire:submit="report" class="mt-3 rounded-ui bg-subtle p-4 text-sm">
                <fieldset>
                    <legend class="mb-2 font-semibold text-ink">Why are you reporting this comment?</legend>
                    <div class="grid gap-1.5 sm:grid-cols-2">
                        @foreach (\App\Models\Comment::REPORT_REASONS as $key => $label)
                            <label class="flex items-center gap-2"><input type="radio" wire:model="reportReason" value="{{ $key }}" class="checkbox !rounded-full"> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-3 flex justify-end gap-2">
                    <button type="button" wire:click="$set('reporting', null)" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Send report</button>
                </div>
            </form>
        @endif
    </div>
</article>
