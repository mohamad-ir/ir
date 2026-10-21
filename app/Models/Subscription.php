<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'membership_plan_id', 'stripe_subscription_id', 'stripe_customer_id', 'status', 'amount', 'currency',
        'interval', 'current_period_end', 'trial_ends_at', 'cancel_at_period_end', 'canceled_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'current_period_end' => 'datetime',
            'trial_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'ended_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function scopeGrantingAccess(Builder $query): Builder
    {
        return $query->whereIn('status', SubscriptionStatus::ACCESS);
    }

    public function grantsAccess(): bool
    {
        return $this->status?->grantsAccess() ?? false;
    }

    public function priceLabel(): ?string
    {
        return $this->amount !== null ? Money::format($this->amount, $this->currency).' / '.($this->interval ?: 'month') : null;
    }

    public function stripeDashboardUrl(): string
    {
        return 'https://dashboard.stripe.com/subscriptions/'.$this->stripe_subscription_id;
    }
}
