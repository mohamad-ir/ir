<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EnquiryReply extends Mailable
{
    /** Emails are always English, whatever language the dashboard is shown in. */
    public $locale = 'en';

    use Queueable;

    public function __construct(public Enquiry $enquiry, public string $body, public string $replySubject) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->replySubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enquiry-reply');
    }
}
