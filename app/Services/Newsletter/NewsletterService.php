<?php

namespace App\Services\Newsletter;

use App\Enums\SubscriberStatus;
use App\Mail\ConfirmSubscription;
use App\Models\ActivityLog;
use App\Models\Subscriber;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NewsletterService
{
    /**
     * @return string 'pending' (confirmation email sent), 'subscribed' or 'already'
     */
    public function subscribe(string $email, ?string $name = null, array $interests = ['weekly_case'], ?string $source = null, ?int $userId = null): string
    {
        $email = mb_strtolower(trim($email));
        $interests = array_values(array_intersect($interests ?: ['weekly_case'], array_keys(Subscriber::INTERESTS)));
        $subscriber = Subscriber::firstOrNew(['email' => $email]);

        if ($subscriber->exists && $subscriber->status === SubscriberStatus::Subscribed) {
            $subscriber->interests = array_values(array_unique(array_merge($subscriber->interests ?? [], $interests)));
            $subscriber->save();

            return 'already';
        }

        $doubleOptIn = (bool) Settings::get('newsletter.double_opt_in', true);

        $subscriber->fill([
            'name' => $name ?: $subscriber->name,
            'interests' => array_values(array_unique(array_merge($subscriber->interests ?? [], $interests))),
            'source' => $subscriber->source ?: $source,
            'user_id' => $userId ?? $subscriber->user_id,
            'status' => $doubleOptIn ? SubscriberStatus::Pending : SubscriberStatus::Subscribed,
            'confirmed_at' => $doubleOptIn ? null : now(),
            'unsubscribed_at' => null,
        ]);
        $subscriber->save();

        if ($doubleOptIn) {
            try {
                Mail::to($subscriber->email)->send(new ConfirmSubscription($subscriber));
            } catch (Throwable $e) {
                report($e);
            }

            return 'pending';
        }

        return 'subscribed';
    }

    public function confirm(Subscriber $subscriber): void
    {
        if ($subscriber->status !== SubscriberStatus::Subscribed) {
            $subscriber->update(['status' => SubscriberStatus::Subscribed, 'confirmed_at' => now(), 'unsubscribed_at' => null]);
        }
    }

    public function unsubscribe(Subscriber $subscriber): void
    {
        $subscriber->update(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()]);
        ActivityLog::record('unsubscribed', "{$subscriber->email} unsubscribed from the newsletter", $subscriber);
    }
}
