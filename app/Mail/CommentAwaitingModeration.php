<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the editorial team a comment is waiting for approval. */
class CommentAwaitingModeration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Comment awaiting approval — '.($this->comment->commentable?->title ?? 'discussion'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.comment-moderation', with: [
            'title' => $this->comment->commentable?->title ?? 'a discussion',
            'url' => url(config('cms.admin_path').'/comments?tab=pending'),
        ]);
    }
}
