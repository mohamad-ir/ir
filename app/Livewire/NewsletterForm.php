<?php

namespace App\Livewire;

use App\Models\AnalyticsEvent;
use App\Services\Analytics\VisitorHash;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class NewsletterForm extends Component
{
    public string $email = '';

    public string $name = '';

    public string $website = ''; // honeypot

    public array $interests = ['weekly_case'];

    public string $source = 'website';

    public string $variant = 'default';

    public bool $dark = false;

    public bool $compact = false;

    public ?string $result = null;

    public function subscribe(NewsletterService $newsletter): void
    {
        if ($this->website !== '') {
            $this->result = 'pending';

            return;
        }

        $key = 'newsletter:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Too many attempts. Please try again in a few minutes.');

            return;
        }
        RateLimiter::hit($key, 600);

        $this->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $this->result = $newsletter->subscribe($this->email, $this->name ?: null, $this->interests, $this->source, auth()->id());

        AnalyticsEvent::create([
            'name' => 'newsletter_signup',
            'visitor_hash' => app(VisitorHash::class)->for(request()),
            'path' => mb_substr((string) parse_url((string) request()->headers->get('referer'), PHP_URL_PATH), 0, 500) ?: null,
            'properties' => ['source' => $this->source, 'result' => $this->result],
            'created_at' => now(),
        ]);

        $this->reset('email', 'name');
    }

    public function render()
    {
        return view('livewire.newsletter-form');
    }
}
