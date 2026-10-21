<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnquiryType: string implements HasColor, HasLabel
{
    case Collaboration = 'collaboration';
    case Speaking = 'speaking';
    case Teaching = 'teaching';
    case Research = 'research';
    case Media = 'media';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Collaboration => 'Scientific collaboration',
            self::Speaking => 'Speaking invitation',
            self::Teaching => 'Teaching',
            self::Research => 'Research collaboration',
            self::Media => 'Media & press',
            self::Other => 'Other enquiry',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Collaboration => 'primary',
            self::Speaking => 'info',
            self::Teaching => 'success',
            self::Research => 'warning',
            self::Media => 'gray',
            self::Other => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
