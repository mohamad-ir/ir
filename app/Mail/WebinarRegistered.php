<?php

namespace App\Mail;

use App\Models\WebinarRegistration;
use App\Support\Ics;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WebinarRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WebinarRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your place is confirmed: '.$this->registration->webinar->title);
    }

    public function content(): Content
    {
        $webinar = $this->registration->webinar;

        return new Content(view: 'emails.learning.webinar-registered', with: [
            'webinar' => $webinar,
            'user' => $this->registration->user,
            'joinUrl' => route('webinars.join', $webinar->slug),
            'pageUrl' => $webinar->url(),
        ]);
    }

    public function attachments(): array
    {
        $webinar = $this->registration->webinar;
        $ics = Ics::event(
            'webinar-'.$webinar->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            $webinar->starts_at, $webinar->endsAt(), $webinar->title,
            'Join: '.route('webinars.join', $webinar->slug)."\n\n".$webinar->summary,
            route('webinars.join', $webinar->slug),
            $webinar->platform?->getLabel(),
        );

        return [Attachment::fromData(fn () => $ics, 'webinar.ics')->withMime('text/calendar; charset=utf-8; method=PUBLISH')];
    }
}
