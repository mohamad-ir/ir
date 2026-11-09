<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasColor, HasLabel
{
    case Sba = 'sba';
    case Image = 'image';
    case Viva = 'viva';
    case Clinical = 'clinical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sba => __('Single best answer (MCQ)'),
            self::Image => __('Image-based question'),
            self::Viva => __('Viva question'),
            self::Clinical => __('Clinical case question'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Sba => 'primary',
            self::Image => 'info',
            self::Viva => 'warning',
            self::Clinical => 'success',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
