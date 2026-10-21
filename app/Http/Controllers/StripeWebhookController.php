<?php

namespace App\Http\Controllers;

use App\Models\PaymentEvent;
use App\Services\Payments\Fulfillment;
use App\Services\Payments\StripeClient;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Receives Stripe events at /stripe/webhook. Stripe retries anything that does not return 2xx. */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, Fulfillment $fulfillment)
    {
        $payload = $request->getContent();

        if (! StripeClient::verifySignature($payload, $request->header('Stripe-Signature'), Settings::get('payments.stripe_webhook_secret'))) {
            Log::warning('Rejected Stripe webhook with an invalid signature from '.$request->ip());

            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || empty($event['id']) || empty($event['type'])) {
            return response('Invalid payload', 400);
        }

        PaymentEvent::insertOrIgnore([
            'stripe_event_id' => $event['id'],
            'type' => $event['type'],
            'livemode' => (bool) ($event['livemode'] ?? false),
            'payload' => $payload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $record = PaymentEvent::where('stripe_event_id', $event['id'])->first();

        if ($record->processed_at) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        try {
            $fulfillment->handle($event);
            $record->update(['processed_at' => now(), 'error' => null]);
        } catch (Throwable $e) {
            report($e);
            $record->update(['error' => mb_substr($e->getMessage(), 0, 2000)]);

            return response('Processing failed; Stripe will retry.', 500);
        }

        return response()->json(['received' => true]);
    }
}
