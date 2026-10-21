<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CareerStage: string implements HasColor, HasLabel
{
    case Student = 'student';
    case Resident = 'resident';
    case Fellow = 'fellow';
    case Ophthalmologist = 'ophthalmologist';
    case Optometrist = 'optometrist';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Student => 'Medical student',
            self::Resident => 'Resident / trainee',
            self::Fellow => 'Fellow',
            self::Ophthalmologist => 'Ophthalmologist',
            self::Optometrist => 'Optometrist / allied professional',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Student => 'gray',
            self::Resident => 'info',
            self::Fellow => 'primary',
            self::Ophthalmologist => 'success',
            self::Optometrist => 'warning',
            self::Other => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
