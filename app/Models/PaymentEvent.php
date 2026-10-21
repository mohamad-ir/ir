<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Every Stripe webhook received, stored for idempotency and troubleshooting. */
class PaymentEvent extends Model
{
    protected $fillable = ['stripe_event_id', 'type', 'livemode', 'payload', 'processed_at', 'error'];

    protected function casts(): array
    {
        return ['livemode' => 'boolean', 'processed_at' => 'datetime'];
    }
}
