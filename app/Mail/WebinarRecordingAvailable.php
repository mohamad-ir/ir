<?php

namespace App\Mail;

use App\Models\WebinarRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WebinarRecordingAvailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WebinarRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recording available: '.$this->registration->webinar->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.learning.webinar-recording', with: [
            'webinar' => $this->registration->webinar,
            'user' => $this->registration->user,
            'url' => $this->registration->webinar->url(),
        ]);
    }
}
