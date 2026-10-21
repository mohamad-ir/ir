<?php

namespace App\Http\Controllers;

use App\Models\Webinar;
use App\Services\Access\ContentAccess;
use App\Services\Learning\LearningService;
use App\Services\Payments\StripeClient;
use App\Services\Seo\SchemaFactory;
use App\Services\Seo\SeoManager;
use App\Support\Ics;
use Illuminate\Http\Request;

class WebinarController extends Controller
{
    public function index(Request $request, SeoManager $seo)
    {
        $seo->title('Live webinars')
            ->description('Live online teaching sessions on OCT, medical retina and uveitis with case discussion and Q&A — plus recordings of past webinars.')
            ->breadcrumb('Webinars', route('webinars.index'));

        $all = Webinar::published()->with('featuredMedia')->get();

        return view('webinars.index', [
            'upcoming' => $all->reject->hasEnded()->sortBy('starts_at')->values(),
            'past' => $all->filter->hasEnded()->sortByDesc('starts_at')->values(),
            'registered' => $request->user() ? $request->user()->webinarRegistrations()->whereNull('canceled_at')->pluck('webinar_id')->all() : [],
        ]);
    }

    public function show(string $slug, Request $request, SeoManager $seo, ContentAccess $access)
    {
        $webinar = Webinar::published()->where('slug', $slug)->with(['featuredMedia', 'author', 'topics', 'seo.ogImage'])->firstOrFail();

        return $this->render($webinar, $request, $seo, $access);
    }

    public function render(Webinar $webinar, Request $request, SeoManager $seo, ContentAccess $access, bool $preview = false)
    {
        $webinar->loadMissing(['featuredMedia', 'author', 'topics']);
        $user = $request->user();

        $seo->forModel($webinar, 'website')
            ->image($webinar->seo?->ogImage ?? $webinar->featuredMedia)
            ->breadcrumb('Webinars', route('webinars.index'))
            ->breadcrumb($webinar->title, $webinar->url())
            ->schema(SchemaFactory::event($webinar));
        if ($preview) {
            $seo->noindex();
        }

        return view('webinars.show', [
            'webinar' => $webinar,
            'registration' => $access->registration($webinar, $user),
            'freeForUser' => $access->webinarFreeFor($webinar, $user),
            'canWatch' => $access->canWatchRecording($webinar, $user),
            'isMember' => (bool) $user?->isMember(),
            'paymentsEnabled' => StripeClient::configured(),
            'isPreview' => $preview,
        ]);
    }

    /** Free webinars and members' free places. Paid tickets go through CheckoutController. */
    public function register(string $slug, Request $request, ContentAccess $access, LearningService $learning)
    {
        $webinar = Webinar::published()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if ($access->registration($webinar, $user)) {
            return back()->with('status', 'You are already registered.');
        }
        if (! $webinar->acceptsRegistrations()) {
            return back()->with('error', $webinar->isFull() ? 'Sorry — this webinar is fully booked.' : 'Registration for this webinar is closed.');
        }
        abort_unless($access->webinarFreeFor($webinar, $user), 403);

        $learning->register($user, $webinar, $webinar->isFree() ? 'free' : 'membership');

        return back()->with('status', 'You are registered! A confirmation with a calendar invitation is on its way to '.$user->email.'.');
    }

    public function cancel(string $slug, Request $request, ContentAccess $access)
    {
        $webinar = Webinar::published()->where('slug', $slug)->firstOrFail();
        $registration = $access->registration($webinar, $request->user()) ?? abort(404);
        abort_if($registration->source === 'purchase', 403, 'Paid tickets can be cancelled by contacting us.');

        $registration->update(['canceled_at' => now()]);

        return back()->with('status', 'Your registration has been cancelled.');
    }

    /** Personal join link used in emails: checks the registration, then forwards to Zoom / YouTube / Teams. */
    public function join(string $slug, Request $request, ContentAccess $access)
    {
        $webinar = Webinar::published()->where('slug', $slug)->firstOrFail();
        $user = $request->user();
        $registration = $access->registration($webinar, $user);

        if (! $registration && ! $user->isStaff()) {
            return redirect($webinar->url())->with('error', 'Please register for this webinar to receive the join link.');
        }
        if (blank($webinar->join_url)) {
            return redirect($webinar->url())->with('error', 'The join link has not been published yet — it will be emailed to you before the start.');
        }
        if (! $webinar->joinWindowOpen() && ! $user->isStaff()) {
            return redirect($webinar->url())->with('status', $webinar->hasEnded()
                ? 'This webinar has ended.'
                : 'The join link opens '.Webinar::JOIN_OPENS_MINUTES.' minutes before the start ('.$webinar->displayStart('j M, H:i').').');
        }

        if ($registration && ! $registration->joined_at) {
            $registration->update(['joined_at' => now()]);
        }

        return redirect()->away($webinar->join_url);
    }

    /** Public calendar file (without the private join link). */
    public function calendar(string $slug)
    {
        $webinar = Webinar::published()->where('slug', $slug)->firstOrFail();
        $ics = Ics::event('webinar-'.$webinar->id.'@'.parse_url(config('app.url'), PHP_URL_HOST), $webinar->starts_at, $webinar->endsAt(),
            $webinar->title, $webinar->summary."\n\n".$webinar->url(), $webinar->url(), $webinar->platform?->getLabel());

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$webinar->slug.'.ics"',
        ]);
    }
}
