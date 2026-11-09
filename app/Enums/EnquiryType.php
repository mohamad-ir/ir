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
            self::Collaboration => __('Scientific collaboration'),
            self::Speaking => __('Speaking invitation'),
            self::Teaching => __('Teaching'),
            self::Research => __('Research collaboration'),
            self::Media => __('Media & press'),
            self::Other => __('Other enquiry'),
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
