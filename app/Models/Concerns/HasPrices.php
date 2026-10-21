<?php

namespace App\Models\Concerns;

use App\Support\Currency;
use App\Support\Money;

/**
 * A base price (price + currency) plus optional prices in other currencies,
 * stored as [['currency' => 'GBP', 'amount' => 8500], …] in minor units.
 */
trait HasPrices
{
    public function initializeHasPrices(): void
    {
        $this->mergeCasts(['prices' => 'array']);
    }

    public function priceIn(string $currency): ?int
    {
        $currency = strtoupper($currency);
        if ($currency === strtoupper((string) $this->currency)) {
            return (int) $this->price;
        }

        foreach ($this->prices ?? [] as $row) {
            if (strtoupper($row['currency'] ?? '') === $currency && is_numeric($row['amount'] ?? null) && (int) $row['amount'] > 0) {
                return (int) $row['amount'];
            }
        }

        return null;
    }

    /** @return array{0: int, 1: string} the price in the visitor's currency when one is set, otherwise the base price */
    public function priceFor(?string $currency = null): array
    {
        $currency = strtoupper($currency ?? Currency::current());
        $amount = $this->priceIn($currency);

        return $amount !== null ? [$amount, $currency] : [(int) $this->price, strtoupper((string) $this->currency)];
    }

    /** @return array<string, int> every currency this item can be bought in */
    public function allPrices(): array
    {
        $all = [strtoupper((string) $this->currency) => (int) $this->price];
        foreach ($this->prices ?? [] as $row) {
            if (! empty($row['currency']) && (int) ($row['amount'] ?? 0) > 0) {
                $all[strtoupper($row['currency'])] ??= (int) $row['amount'];
            }
        }

        return $all;
    }

    public function formattedPrice(?string $currency = null): string
    {
        return Money::format(...$this->priceFor($currency));
    }
}
