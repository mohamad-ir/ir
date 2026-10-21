<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmSubscription extends Mailable
{
    use Queueable;

    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Please confirm your subscription to '.setting('site.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirm-subscription', with: [
            'confirmUrl' => route('newsletter.confirm', $this->subscriber->token),
        ]);
    }
}
