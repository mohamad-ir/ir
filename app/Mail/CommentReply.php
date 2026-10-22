<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/** Tells a member that someone replied to their comment. */
class CommentReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->comment->authorName().' replied to your comment');
    }

    public function content(): Content
    {
        $recipient = $this->comment->parent?->user;

        return new Content(view: 'emails.comment-reply', with: [
            'title' => $this->comment->commentable?->title ?? 'a discussion',
            'url' => $this->comment->url(),
            'unsubscribeUrl' => $recipient ? URL::signedRoute('comments.unsubscribe', ['user' => $recipient->id]) : null,
        ]);
    }
}
