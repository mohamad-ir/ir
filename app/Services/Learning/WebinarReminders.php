<?php

namespace App\Services\Learning;

use App\Mail\WebinarReminder;
use App\Models\Webinar;
use App\Models\WebinarRegistration;

/** Sends "tomorrow" and "in one hour" reminders. Runs every five minutes from the scheduler. */
class WebinarReminders
{
    public function __construct(protected LearningService $learning) {}

    public function send(): int
    {
        $sent = 0;
        $webinars = Webinar::published()->whereBetween('starts_at', [now()->subMinutes(5), now()->addHours(25)])->get();

        foreach ($webinars as $webinar) {
            $minutesToStart = now()->diffInMinutes($webinar->starts_at, false);

            $registrations = WebinarRegistration::where('webinar_id', $webinar->id)->whereNull('canceled_at')->with('user')->get();
            foreach ($registrations as $registration) {
                if (! $registration->user) {
                    continue;
                }

                if ($minutesToStart <= 70 && ! $registration->reminder_hour_sent_at) {
                    $registration->update(['reminder_hour_sent_at' => now(), 'reminder_day_sent_at' => $registration->reminder_day_sent_at ?? now()]);
                    $this->learning->mail($registration->user, new WebinarReminder($registration->setRelation('webinar', $webinar), 'hour'));
                    $sent++;
                } elseif ($minutesToStart > 120 && $minutesToStart <= 24 * 60 && ! $registration->reminder_day_sent_at
                    && $registration->created_at->lt($webinar->starts_at->copy()->subHours(24))) {
                    $registration->update(['reminder_day_sent_at' => now()]);
                    $this->learning->mail($registration->user, new WebinarReminder($registration->setRelation('webinar', $webinar), 'day'));
                    $sent++;
                }
            }
        }

        return $sent;
    }
}
