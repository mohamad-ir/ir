<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccessLevel: string implements HasColor, HasLabel
{
    case Public = 'public';
    case Registered = 'registered';
    case Members = 'members';

    public function getLabel(): string
    {
        return match ($this) {
            self::Public => 'Everyone',
            self::Registered => 'Registered users (free account)',
            self::Members => 'Paying members',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Public => 'success',
            self::Registered => 'info',
            self::Members => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
