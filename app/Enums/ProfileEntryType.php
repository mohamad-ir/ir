<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProfileEntryType: string implements HasColor, HasLabel
{
    case Education = 'education';
    case Fellowship = 'fellowship';
    case Appointment = 'appointment';
    case Surgical = 'surgical';
    case Publication = 'publication';
    case Book = 'book';
    case Lecture = 'lecture';
    case Teaching = 'teaching';
    case Award = 'award';
    case Membership = 'membership';

    public function getLabel(): string
    {
        return match ($this) {
            self::Education => 'Education',
            self::Fellowship => 'Fellowship & training',
            self::Appointment => 'Clinical appointment',
            self::Surgical => 'Surgical expertise',
            self::Publication => 'Publication',
            self::Book => 'Book & chapter',
            self::Lecture => 'Lecture & presentation',
            self::Teaching => 'Teaching role',
            self::Award => 'Award & honour',
            self::Membership => 'Professional membership',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Education => 'primary',
            self::Fellowship => 'info',
            self::Appointment => 'success',
            self::Surgical => 'warning',
            self::Publication => 'primary',
            self::Book => 'info',
            self::Lecture => 'success',
            self::Teaching => 'warning',
            self::Award => 'danger',
            self::Membership => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
