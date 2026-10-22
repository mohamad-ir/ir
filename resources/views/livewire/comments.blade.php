<section id="comments" class="scroll-mt-24" aria-labelledby="comments-heading">
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-4">
        <div>
            <p class="eyebrow">Discussion</p>
            <h2 id="comments-heading" class="mt-1 font-heading text-2xl font-semibold text-ink">
                {{ $count ? $count.' '.\Illuminate\Support\Str::plural('comment', $count) : 'Comments' }}
            </h2>
        </div>
        @if ($total > 1)
            <div class="flex gap-1 rounded-ui bg-subtle p-1 text-sm" role="group" aria-label="Sort comments">
                @foreach (['top' => 'Top', 'newest' => 'Newest', 'oldest' => 'Oldest'] as $key => $label)
                    <button type="button" wire:click="setSort('{{ $key }}')" aria-pressed="{{ $sort === $key ? 'true' : 'false' }}"
                        @class(['rounded-[calc(var(--radius-ui)-2px)] px-3 py-1.5 font-medium transition', 'bg-surface text-ink shadow-sm' => $sort === $key, 'text-muted hover:text-ink' => $sort !== $key])>{{ $label }}</button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($notice)
        <div class="mt-5 flex items-start gap-3 rounded-ui bg-accent/[.08] p-4 text-sm text-fg" role="status">
            <x-heroicon-o-information-circle class="mt-0.5 size-5 shrink-0 text-accent-ink" />
            <p class="flex-1">{{ $notice }}</p>
            <button type="button" wire:click="$set('notice', null)" class="text-muted hover:text-ink" aria-label="Dismiss"><x-heroicon-m-x-mark class="size-4" /></button>
        </div>
    @endif

    {{-- New comment --}}
    <div class="mt-6">
        @if (! $open)
            <p class="flex items-center gap-2 rounded-ui bg-subtle p-4 text-sm text-muted"><x-heroicon-o-lock-closed class="size-4" /> Comments are closed for this page.</p>
        @elseif (! auth()->check())
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-card bg-subtle p-5">
                <p class="text-sm text-fg/80"><strong class="text-ink">Join the discussion.</strong> Sign in or create a free account to comment.</p>
                <div class="flex gap-2">
                    <a href="{{ route('login', ['redirect' => $returnTo]) }}" class="btn btn-secondary btn-sm">Sign in</a>
                    <a href="{{ route('register', ['redirect' => $returnTo]) }}" class="btn btn-primary btn-sm">Create free account</a>
                </div>
            </div>
        @elseif ($needsVerification)
            <p class="rounded-ui bg-highlight/10 p-4 text-sm text-fg">Please confirm your email address to join the discussion. <a href="{{ route('verification.notice') }}" class="link">Resend the confirmation email</a>.</p>
        @else
            <form wire:submit="post" class="flex gap-3 sm:gap-4">
                @include('livewire.comments.avatar', ['person' => auth()->user()])
                <div class="min-w-0 flex-1">
                    <label for="comment-body" class="sr-only">Your comment</label>
                    <textarea id="comment-body" wire:model="body" rows="3" maxlength="{{ \App\Services\Comments\CommentService::MAX_LENGTH }}" class="input min-h-24 resize-y" placeholder="Share your interpretation, a question or a teaching point…" required></textarea>
                    <div class="absolute -left-[9999px]" aria-hidden="true"><label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>
                    @error('body')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-muted">@if ($guidelines){{ $guidelines }}@endif</p>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="post">
                            <span wire:loading.remove wire:target="post">Post comment</span><span wire:loading wire:target="post">Posting…</span>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    {{-- Threads --}}
    @if ($threads->isEmpty())
        @if ($open)<p class="mt-8 text-center text-sm text-muted">No comments yet — start the discussion.</p>@endif
    @else
        <ol class="mt-8 space-y-7">
            @foreach ($threads as $comment)
                <li wire:key="c-{{ $comment->id }}">
                    @include('livewire.comments.item', ['comment' => $comment, 'isReply' => false])
                    @if ($comment->replies->isNotEmpty() || $replyTo === $comment->id)
                        <ol class="mt-4 space-y-4 border-l-2 border-line pl-4 sm:ml-5 sm:pl-6">
                            @foreach ($comment->replies as $reply)
                                <li wire:key="c-{{ $reply->id }}">@include('livewire.comments.item', ['comment' => $reply, 'isReply' => true])</li>
                            @endforeach
                            @if ($replyTo === $comment->id)
                                <li>
                                    <form wire:submit="reply" class="flex gap-3">
                                        @include('livewire.comments.avatar', ['person' => auth()->user(), 'small' => true])
                                        <div class="min-w-0 flex-1">
                                            <label for="reply-body" class="sr-only">Your reply</label>
                                            <textarea id="reply-body" wire:model="replyBody" rows="2" class="input resize-y" placeholder="Reply to {{ $comment->authorName() }}…" x-init="$el.focus()" maxlength="{{ \App\Services\Comments\CommentService::MAX_LENGTH }}"></textarea>
                                            @error('replyBody')<p class="field-error">{{ $message }}</p>@enderror
                                            <div class="mt-2 flex justify-end gap-2">
                                                <button type="button" wire:click="$set('replyTo', null)" class="btn btn-ghost btn-sm">Cancel</button>
                                                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="reply">Reply</button>
                                            </div>
                                        </div>
                                    </form>
                                </li>
                            @endif
                        </ol>
                    @endif
                </li>
            @endforeach
        </ol>
        @if ($total > $threads->count())
            <div class="mt-8 text-center">
                <button type="button" wire:click="loadMore" class="btn btn-secondary btn-sm" wire:loading.attr="disabled" wire:target="loadMore">Show more comments ({{ $total - $threads->count() }})</button>
            </div>
        @endif
    @endif

    @if ($moderator)
        <div class="mt-8 flex flex-wrap items-center justify-end gap-3 border-t border-line pt-4 text-xs text-muted">
            <span>Moderator tools:</span>
            <button type="button" wire:click="toggleComments" class="link">{{ $item->comments_enabled ? 'Close comments on this page' : 'Reopen comments' }}</button>
            <a href="{{ url(config('cms.admin_path').'/comments') }}" class="link">All comments</a>
        </div>
    @endif
</section>
