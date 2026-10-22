<?php

namespace App\Mail;

use App\Models\Webinar;
use App\Support\RichText;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A message from the organiser to everyone registered for a webinar. */
class WebinarMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Webinar $webinar, public string $messageSubject, public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->messageSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.learning.webinar-message', with: [
            'webinar' => $this->webinar,
            'html' => (string) RichText::renderForEmail($this->body),
            'joinUrl' => route('webinars.join', $this->webinar->slug),
        ]);
    }
}
