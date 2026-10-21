<?php

namespace App\Mail;

use App\Models\WebinarRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WebinarReminder extends Mailable
{
    use Queueable, SerializesModels;

    /** @param 'day'|'hour' $when */
    public function __construct(public WebinarRegistration $registration, public string $when) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->when === 'hour' ? 'Starting in one hour: ' : 'Tomorrow: ').$this->registration->webinar->title);
    }

    public function content(): Content
    {
        $webinar = $this->registration->webinar;

        return new Content(view: 'emails.learning.webinar-reminder', with: [
            'webinar' => $webinar,
            'user' => $this->registration->user,
            'when' => $this->when,
            'joinUrl' => route('webinars.join', $webinar->slug),
        ]);
    }
}
