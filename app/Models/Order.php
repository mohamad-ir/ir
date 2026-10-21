<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order extends Model
{
    protected $fillable = [
        'number', 'user_id', 'email', 'name', 'type', 'purchasable_type', 'purchasable_id', 'description',
        'subtotal', 'tax', 'total', 'refunded_amount', 'currency', 'status', 'stripe_checkout_session_id',
        'stripe_payment_intent_id', 'stripe_invoice_id', 'stripe_subscription_id', 'receipt_url', 'invoice_pdf_url',
        'consent_at', 'paid_at', 'refunded_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'consent_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->number ??= static::nextNumber();
        });
    }

    /** Human-friendly sequential reference, e.g. OA-2026-00042. */
    public static function nextNumber(): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) setting('site.name', 'OA')), 0, 2) ?: 'OA');
        $year = now()->year;
        $last = static::where('number', 'like', "{$prefix}-{$year}-%")->orderByDesc('id')->value('number');
        $next = $last ? ((int) substr($last, strrpos($last, '-') + 1)) + 1 : 1;

        return sprintf('%s-%d-%05d', $prefix, $year, $next);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    public function totalLabel(): string
    {
        return Money::format($this->total, $this->currency);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true);
    }

    public function stripeDashboardUrl(): ?string
    {
        return match (true) {
            filled($this->stripe_payment_intent_id) => 'https://dashboard.stripe.com/payments/'.$this->stripe_payment_intent_id,
            filled($this->stripe_invoice_id) => 'https://dashboard.stripe.com/invoices/'.$this->stripe_invoice_id,
            default => null,
        };
    }
}
