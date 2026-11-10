<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Time of the Stripe state last applied to a subscription. Stripe does not deliver webhooks in
 * order and retries for days, so an older event must never overwrite a newer state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', fn (Blueprint $t) => $t->timestamp('stripe_synced_at')->nullable()->after('ended_at'));
    }

    public function down(): void
    {
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropColumn('stripe_synced_at'));
    }
};
