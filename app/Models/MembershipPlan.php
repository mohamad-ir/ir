<?php

namespace App\Models;

use App\Models\Concerns\HasPrices;
use App\Models\Concerns\LogsActivity;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use HasFactory, HasPrices, LogsActivity;

    protected $fillable = [
        'name', 'slug', 'tagline', 'description', 'features', 'price', 'currency', 'prices', 'interval', 'trial_days',
        'is_featured', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['stripe_prices' => 'array', 'features' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean', 'price' => 'integer', 'trial_days' => 'integer'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function priceLabel(): string
    {
        return $this->formattedPrice();
    }

    public function intervalLabel(): string
    {
        return $this->interval === 'year' ? 'year' : 'month';
    }

    /** Monthly equivalent of a yearly plan, e.g. "€8.25 / month". */
    public function monthlyEquivalent(): ?string
    {
        [$amount, $currency] = $this->priceFor();

        return $this->interval === 'year' ? Money::format((int) round($amount / 12), $currency) : null;
    }

    /** Changes whenever Stripe needs a new Price object (Stripe prices are immutable). */
    public function priceFingerprint(string $currency): string
    {
        return implode(':', [$this->priceIn($currency), strtolower($currency), $this->interval]);
    }
}
