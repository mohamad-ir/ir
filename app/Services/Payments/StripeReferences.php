<?php

namespace App\Services\Payments;

use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\Webinar;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/**
 * Test-mode and live-mode Stripe objects are separate. When the secret key switches
 * between modes, stored customer/product/price IDs are cleared so they are recreated.
 */
class StripeReferences
{
    public static function modeOf(?string $key): ?string
    {
        return match (true) {
            blank($key) => null,
            str_contains($key, '_test_') => 'test',
            default => 'live',
        };
    }

    /** @return bool true when references were reset */
    public static function syncMode(): bool
    {
        $mode = self::modeOf(Settings::get('payments.stripe_secret_key'));
        $previous = Settings::get('payments.key_mode');
        if (! $mode || $mode === $previous) {
            return false;
        }

        if ($previous) {
            DB::transaction(function () {
                User::whereNotNull('stripe_customer_id')->update(['stripe_customer_id' => null]);
                MembershipPlan::query()->update(['stripe_product_id' => null, 'stripe_price_id' => null, 'stripe_price_fingerprint' => null]);
                Course::withTrashed()->update(['stripe_product_id' => null]);
                Webinar::withTrashed()->update(['stripe_product_id' => null]);
            });
        }
        Settings::set('payments.key_mode', $mode);

        return (bool) $previous;
    }
}
