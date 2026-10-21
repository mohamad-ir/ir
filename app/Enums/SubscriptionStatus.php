<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Mirrors Stripe's subscription statuses. */
enum SubscriptionStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Trialing = 'trialing';
    case PastDue = 'past_due';
    case Incomplete = 'incomplete';
    case IncompleteExpired = 'incomplete_expired';
    case Unpaid = 'unpaid';
    case Paused = 'paused';
    case Canceled = 'canceled';

    /** Statuses that keep member access. Past-due keeps access while Stripe retries the card. */
    public const ACCESS = ['active', 'trialing', 'past_due'];

    public function grantsAccess(): bool
    {
        return in_array($this->value, self::ACCESS, true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Trialing => 'Free trial',
            self::PastDue => 'Payment overdue',
            self::Incomplete => 'Incomplete',
            self::IncompleteExpired => 'Expired',
            self::Unpaid => 'Unpaid',
            self::Paused => 'Paused',
            self::Canceled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Trialing => 'info',
            self::PastDue, self::Unpaid, self::Incomplete => 'warning',
            default => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
