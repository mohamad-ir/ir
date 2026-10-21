<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Webinar;
use App\Services\Access\ContentAccess;
use App\Services\Payments\Checkout;
use App\Services\Payments\Fulfillment;
use App\Services\Payments\StripeClient;
use App\Services\Payments\StripeException;
use App\Services\Seo\SeoManager;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function membership(Request $request, MembershipPlan $plan, Checkout $checkout)
    {
        abort_unless($plan->is_active, 404);
        $user = $request->user();

        if ($user->isMember()) {
            return redirect()->route('account.billing')->with('status', 'You already have an active membership.');
        }

        return $this->redirectToStripe(fn () => $checkout->membership($user, $plan));
    }

    public function course(Request $request, string $slug, Checkout $checkout, ContentAccess $access)
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if ($access->canAccessCourse($course, $user)) {
            return redirect()->route('courses.show', $course->slug)->with('status', 'You already have access to this course.');
        }
        abort_if($course->isFree(), 404);
        $this->validateConsent($request);

        return $this->redirectToStripe(fn () => $checkout->course($user, $course, $request->boolean('waiver')));
    }

    public function webinar(Request $request, string $slug, Checkout $checkout, ContentAccess $access)
    {
        $webinar = Webinar::published()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if ($access->registration($webinar, $user)) {
            return redirect($webinar->url())->with('status', 'You are already registered for this webinar.');
        }
        if (! $webinar->acceptsRegistrations()) {
            return redirect($webinar->url())->with('error', 'Registration for this webinar is closed.');
        }
        abort_if($access->webinarFreeFor($webinar, $user), 404);
        $this->validateConsent($request);

        return $this->redirectToStripe(fn () => $checkout->webinar($user, $webinar, $request->boolean('waiver')));
    }

    /** Stripe redirects here after payment. Access is granted now, without waiting for the webhook. */
    public function success(Request $request, StripeClient $stripe, Fulfillment $fulfillment, SeoManager $seo)
    {
        $seo->title('Thank you')->noindex();
        $sessionId = (string) $request->query('session_id');
        abort_unless(str_starts_with($sessionId, 'cs_'), 404);

        try {
            $session = $stripe->get('checkout/sessions/'.$sessionId);
        } catch (StripeException $e) {
            Log::warning('Could not load Checkout session on the success page: '.$e->getMessage());

            return view('checkout.result', ['state' => 'processing']);
        }

        abort_unless((string) ($session['client_reference_id'] ?? '') === (string) $request->user()->id, 403);

        $fulfillment->fulfilCheckoutSession($session);
        $type = $session['metadata']['type'] ?? null;

        if ($type !== 'membership' && ($session['payment_status'] ?? null) !== 'paid') {
            return view('checkout.result', ['state' => 'pending']);
        }

        $request->user()->forgetMembership();

        $itemId = $session['metadata']['item_id'] ?? 0;
        $course = $type === 'course' ? Course::find($itemId) : null;
        $webinar = $type === 'webinar' ? Webinar::find($itemId) : null;

        return match (true) {
            $type === 'membership' => redirect()->route('account.dashboard')->with('status', 'Welcome! Your membership is active — everything is now unlocked.'),
            $course !== null => redirect()->route('courses.show', $course->slug)->with('status', 'Payment received — you are enrolled. Enjoy the course!'),
            $webinar !== null => redirect()->route('webinars.show', $webinar->slug)->with('status', 'Payment received — your place is confirmed. Check your inbox for the calendar invitation.'),
            default => redirect()->route('account.dashboard')->with('status', 'Payment received — thank you.'),
        };
    }

    public function cancel(SeoManager $seo)
    {
        $seo->title('Payment cancelled')->noindex();

        return view('checkout.result', ['state' => 'cancelled']);
    }

    public function portal(Request $request, Checkout $checkout)
    {
        return $this->redirectToStripe(fn () => $checkout->portal($request->user()), route('account.billing'));
    }

    protected function validateConsent(Request $request): void
    {
        $request->validate(array_filter([
            'terms' => ['accepted'],
            'waiver' => Settings::get('payments.withdrawal_waiver', true) ? ['accepted'] : null,
        ]), [
            'terms.accepted' => 'Please accept the terms of sale to continue.',
            'waiver.accepted' => 'Please confirm that you want immediate access.',
        ]);
    }

    protected function redirectToStripe(callable $createUrl, ?string $fallback = null)
    {
        if (! StripeClient::configured()) {
            return ($fallback ? redirect($fallback) : back())->with('error', 'Online payments are not available yet. Please try again later or contact us.');
        }

        try {
            return redirect()->away($createUrl());
        } catch (StripeException $e) {
            Log::error('Stripe checkout failed: '.$e->getMessage());

            return ($fallback ? redirect($fallback) : back())->with('error', 'We could not start the payment. Please try again in a moment — you have not been charged.');
        }
    }
}
