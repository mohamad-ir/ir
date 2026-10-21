<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VideoProvider: string implements HasColor, HasLabel
{
    case Youtube = 'youtube';
    case Vimeo = 'vimeo';
    case File = 'file';
    case Url = 'url';

    public function getLabel(): string
    {
        return match ($this) {
            self::Youtube => 'YouTube',
            self::Vimeo => 'Vimeo',
            self::File => 'Uploaded file',
            self::Url => 'External video URL',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Youtube => 'danger',
            self::Vimeo => 'info',
            self::File => 'gray',
            self::Url => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
