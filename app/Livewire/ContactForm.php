<?php

namespace App\Livewire;

use App\Enums\EnquiryType;
use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use App\Support\Settings;
use App\Support\Turnstile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class ContactForm extends Component
{
    public ?string $type = null;

    public string $name = '';

    public string $email = '';

    public string $organisation = '';

    public string $subject = '';

    public string $message = '';

    public bool $consent = false;

    public string $website = ''; // honeypot

    public string $turnstileToken = '';

    public bool $sent = false;

    public int $renderedAt = 0;

    public function mount(?string $type = null): void
    {
        $this->type = EnquiryType::tryFrom((string) $type)?->value ?? EnquiryType::Collaboration->value;
        $this->renderedAt = time();
    }

    public function submit(): void
    {
        // Bots: hidden field filled, or form submitted impossibly fast.
        if ($this->website !== '' || time() - $this->renderedAt < 3) {
            $this->sent = true;

            return;
        }

        $key = 'contact:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 4)) {
            $this->addError('message', 'You have sent several messages recently. Please try again later.');

            return;
        }

        $data = $this->validate([
            'type' => ['required', Rule::enum(EnquiryType::class)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:20', 'max:6000'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Please confirm that this is not a request for medical advice.',
        ]);

        if (Turnstile::enabled() && ! Turnstile::verify($this->turnstileToken, request()->ip())) {
            $this->addError('message', 'Spam check failed — please try again.');

            return;
        }

        RateLimiter::hit($key, 3600);

        $enquiry = Enquiry::create([
            ...collect($data)->except('consent')->all(),
            'status' => 'new',
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
        ]);

        $to = Settings::get('site.notification_email') ?: Settings::get('site.contact_email');
        if ($to) {
            try {
                Mail::to($to)->send(new EnquiryReceived($enquiry));
            } catch (Throwable $e) {
                report($e);
            }
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.contact-form', ['types' => EnquiryType::options()]);
    }
}
