<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Subscriber;
use App\Services\Newsletter\NewsletterService;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function show(SeoManager $seo)
    {
        $seo->title('Newsletter')->description(setting('newsletter.signup_text'))->breadcrumb('Newsletter');

        return view('newsletter.show');
    }

    public function confirm(string $token, NewsletterService $newsletter, SeoManager $seo)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $newsletter->confirm($subscriber);
        $seo->title('Subscription confirmed')->noindex();

        return view('newsletter.message', [
            'title' => 'You’re subscribed',
            'text' => 'Thank you for confirming. The next Case of the Week will arrive in your inbox — meanwhile, explore the case library.',
            'icon' => 'heroicon-o-check-badge',
            'cta' => ['label' => 'Browse OCT cases', 'url' => route('cases.index')],
        ]);
    }

    public function unsubscribe(string $token, SeoManager $seo)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $seo->title('Unsubscribe')->noindex();

        return view('newsletter.unsubscribe', ['subscriber' => $subscriber]);
    }

    public function unsubscribeConfirm(string $token, NewsletterService $newsletter, SeoManager $seo)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $newsletter->unsubscribe($subscriber);
        $seo->title('Unsubscribed')->noindex();

        return view('newsletter.message', [
            'title' => 'You have been unsubscribed',
            'text' => 'You will no longer receive our emails. You can re-subscribe at any time.',
            'icon' => 'heroicon-o-envelope',
            'cta' => ['label' => 'Back to the website', 'url' => url('/')],
        ]);
    }

    public function open(Campaign $campaign, string $token)
    {
        $this->recipient($campaign, $token)?->whereNull('opened_at')->update(['opened_at' => now()]);

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** Signature of a tracked link, so the click tracker cannot be used to send people to other sites. */
    public static function linkSignature(int $campaignId, string $url): string
    {
        return substr(hash_hmac('sha256', $campaignId.'|'.$url, (string) config('app.key')), 0, 32);
    }

    public function click(Request $request, Campaign $campaign, string $token)
    {
        $url = (string) $request->query('u');
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('~^https?://~i', $url)) {
            return redirect('/');
        }
        // Only links that are in this campaign (signed links; emails sent by earlier versions: the link appears in its text).
        $signed = hash_equals(self::linkSignature($campaign->id, $url), (string) $request->query('s'));
        $own = parse_url($url, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST);
        if (! $signed && ! $own && ! str_contains((string) $campaign->body, $url) && ! str_contains((string) $campaign->body, e($url))) {
            return redirect('/');
        }

        $recipient = $this->recipient($campaign, $token);
        if ($recipient) {
            (clone $recipient)->whereNull('clicked_at')->update(['clicked_at' => now()]);
            (clone $recipient)->whereNull('opened_at')->update(['opened_at' => now()]);
        }

        return redirect()->away($url);
    }

    protected function recipient(Campaign $campaign, string $token)
    {
        $subscriber = Subscriber::where('token', $token)->first(['id']);

        return $subscriber ? CampaignRecipient::where('campaign_id', $campaign->id)->where('subscriber_id', $subscriber->id) : null;
    }
}
