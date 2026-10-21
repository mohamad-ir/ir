<?php

namespace App\Mail;

use App\Models\Certificate;
use App\Services\Learning\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Certificate $certificate) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your certificate: '.$this->certificate->course_title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.learning.certificate', with: ['certificate' => $this->certificate]);
    }

    public function attachments(): array
    {
        $service = app(CertificateService::class);

        return [Attachment::fromData(fn () => $service->pdf($this->certificate), $service->filename($this->certificate))->withMime('application/pdf')];
    }
}
