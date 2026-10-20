<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Exam: string implements HasColor, HasLabel
{
    case All = 'all';
    case FrcophthPart1 = 'frcophth_part1';
    case FrcophthPart2Written = 'frcophth_part2_written';
    case FrcophthPart2Oral = 'frcophth_part2_oral';
    case Frcs = 'frcs';

    public function getLabel(): string
    {
        return match ($this) {
            self::All => 'All examinations',
            self::FrcophthPart1 => 'FRCOphth Part 1',
            self::FrcophthPart2Written => 'FRCOphth Part 2 Written',
            self::FrcophthPart2Oral => 'FRCOphth Part 2 Oral / Viva',
            self::Frcs => 'FRCS Ophthalmology',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::All => 'gray',
            self::FrcophthPart1 => 'info',
            self::FrcophthPart2Written => 'primary',
            self::FrcophthPart2Oral => 'warning',
            self::Frcs => 'success',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
