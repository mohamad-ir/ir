<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WebinarPlatform: string implements HasLabel
{
    case Zoom = 'zoom';
    case YoutubeLive = 'youtube';
    case Teams = 'teams';
    case GoogleMeet = 'meet';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Zoom => __('Zoom'),
            self::YoutubeLive => __('YouTube Live'),
            self::Teams => __('Microsoft Teams'),
            self::GoogleMeet => __('Google Meet'),
            self::Other => __('Other platform'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
