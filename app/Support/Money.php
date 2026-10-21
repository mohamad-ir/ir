<?php

namespace App\Support;

use NumberFormatter;

/** Formats and converts amounts stored in minor units (cents). */
class Money
{
    /** Currencies offered in the dashboard; all are supported by Stripe in Europe. */
    public const CURRENCIES = ['EUR' => 'Euro (€)', 'GBP' => 'British pound (£)', 'USD' => 'US dollar ($)', 'CHF' => 'Swiss franc (CHF)'];

    public static function format(?int $minor, ?string $currency): string
    {
        $currency = strtoupper($currency ?: 'EUR');
        $amount = ($minor ?? 0) / 100;

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter('en_GB', NumberFormatter::CURRENCY);
            if (fmod($amount, 1.0) === 0.0) {
                $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
            }
            $formatted = $formatter->formatCurrency($amount, $currency);
            if ($formatted !== false) {
                return $formatted;
            }
        }

        return $currency.' '.number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }

    public static function toMinor(float|int|string|null $major): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $major)) * 100);
    }

    public static function toMajor(?int $minor): ?string
    {
        return $minor === null ? null : number_format($minor / 100, 2, '.', '');
    }
}
