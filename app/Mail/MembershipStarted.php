<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipStarted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to '.setting('site.name').' membership');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.learning.membership-started', with: [
            'subscription' => $this->subscription,
            'user' => $this->subscription->user,
            'url' => route('account.dashboard'),
            'billingUrl' => route('account.billing'),
        ]);
    }
}
