<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Support\RichText;
use App\Support\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

class CampaignMail extends Mailable
{
    use Queueable;

    public function __construct(public Campaign $campaign, public Subscriber $subscriber, public bool $test = false) {}

    public function envelope(): Envelope
    {
        $from = Settings::get('newsletter.from_email');

        return new Envelope(
            from: $from ? new Address($from, Settings::get('newsletter.from_name') ?: Settings::get('site.name')) : null,
            subject: ($this->test ? '[TEST] ' : '').$this->campaign->subject,
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.route('newsletter.unsubscribe', $this->subscriber->token).'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        $html = (string) RichText::render($this->campaign->body);
        $token = $this->subscriber->token;

        if (! $this->test) {
            // Route links through the click tracker.
            $html = preg_replace_callback('~href="(https?://[^"]+)"~i', fn ($m) => 'href="'.e(route('newsletter.click', [$this->campaign->id, $token, 'u' => html_entity_decode($m[1])])).'"', $html);
        }

        return new Content(view: 'emails.campaign', with: [
            'campaign' => $this->campaign,
            'html' => $html,
            'subscriber' => $this->subscriber,
            'unsubscribeUrl' => route('newsletter.unsubscribe', $token),
            'pixel' => ! $this->test && Settings::get('newsletter.track_opens', true) ? route('newsletter.open', [$this->campaign->id, $token]) : null,
        ]);
    }
}
