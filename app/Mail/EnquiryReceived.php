<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EnquiryReceived extends Mailable
{
    use Queueable;

    public function __construct(public Enquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
            subject: 'New enquiry: '.$this->enquiry->type->getLabel().' — '.$this->enquiry->subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enquiry-received', with: ['url' => url(config('cms.admin_path').'/enquiries/'.$this->enquiry->id)]);
    }
}
