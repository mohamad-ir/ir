<?php

namespace App\Services\Newsletter;

use App\Enums\CampaignStatus;
use App\Enums\SubscriberStatus;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Subscriber;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends campaigns in small batches so shared-hosting mail limits are respected.
 * Batches are processed by the scheduler (cron) and, while the dashboard is
 * open, by the "Send now" progress screen.
 */
class CampaignSender
{
    public function audienceQuery(Campaign $campaign)
    {
        $interests = array_filter((array) ($campaign->audience['interests'] ?? []));

        return Subscriber::query()->where('status', SubscriberStatus::Subscribed->value)
            ->when($interests, fn ($q) => $q->where(function ($w) use ($interests) {
                foreach ($interests as $interest) {
                    $w->orWhereJsonContains('interests', $interest);
                }
            }));
    }

    /** Snapshot the recipient list and mark the campaign as sending. */
    public function start(Campaign $campaign): int
    {
        if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Scheduled], true)) {
            return 0;
        }

        $count = 0;
        DB::transaction(function () use ($campaign, &$count) {
            $this->audienceQuery($campaign)->select('id')->chunkById(500, function ($subs) use ($campaign, &$count) {
                $rows = $subs->map(fn ($s) => ['campaign_id' => $campaign->id, 'subscriber_id' => $s->id, 'status' => 'pending'])->all();
                CampaignRecipient::insertOrIgnore($rows);
                $count += count($rows);
            });
            $campaign->update([
                'status' => CampaignStatus::Sending,
                'started_at' => now(),
                'recipients_count' => $campaign->recipients()->count(),
            ]);
        });

        return $count;
    }

    /** @return int number of emails sent in this batch */
    public function sendBatch(Campaign $campaign, ?int $limit = null): int
    {
        $limit ??= max(1, (int) Settings::get('newsletter.send_rate_per_minute', 20));

        if ($campaign->status !== CampaignStatus::Sending) {
            return 0;
        }

        $recipients = $campaign->recipients()->where('status', 'pending')->with('subscriber')->limit($limit)->get();
        $sent = 0;

        foreach ($recipients as $recipient) {
            $subscriber = $recipient->subscriber;
            if (! $subscriber || $subscriber->status !== SubscriberStatus::Subscribed) {
                $recipient->update(['status' => 'skipped']);

                continue;
            }

            try {
                Mail::to($subscriber->email)->send(new CampaignMail($campaign, $subscriber));
                $recipient->update(['status' => 'sent', 'sent_at' => now()]);
                $sent++;
            } catch (Throwable $e) {
                $recipient->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
            }
        }

        $this->refreshCounts($campaign);

        if (! $campaign->recipients()->where('status', 'pending')->exists()) {
            $campaign->update(['status' => CampaignStatus::Sent, 'finished_at' => now()]);
        }

        return $sent;
    }

    public function refreshCounts(Campaign $campaign): void
    {
        $counts = $campaign->recipients()->selectRaw("SUM(status = 'sent') as sent, SUM(status = 'failed') as failed, SUM(opened_at IS NOT NULL) as opens, SUM(clicked_at IS NOT NULL) as clicks")->first();
        $campaign->update([
            'sent_count' => (int) $counts->sent,
            'failed_count' => (int) $counts->failed,
            'opens_count' => (int) $counts->opens,
            'clicks_count' => (int) $counts->clicks,
        ]);
    }

    /** Called every minute by the scheduler. */
    public function tick(): void
    {
        Campaign::where('status', CampaignStatus::Scheduled->value)->where('scheduled_at', '<=', now())->get()
            ->each(fn (Campaign $c) => $this->start($c));

        Campaign::where('status', CampaignStatus::Sending->value)->orderBy('started_at')->get()
            ->each(fn (Campaign $c) => $this->sendBatch($c));
    }

    public function sendTest(Campaign $campaign, string $email): void
    {
        $fake = new Subscriber(['email' => $email, 'name' => 'Test recipient']);
        $fake->token = str_repeat('0', 64);
        Mail::to($email)->send(new CampaignMail($campaign, $fake, test: true));
    }
}
