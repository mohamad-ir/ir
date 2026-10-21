<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Webinar;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class WebinarsBlock extends PageBlock
{
    public static function key(): string { return 'webinars'; }

    public static function label(): string { return 'Upcoming webinars'; }

    public static function icon(): string { return 'heroicon-o-video-camera'; }

    public static function description(): string { return 'Next live sessions with date, time and registration. Hidden when none are scheduled.'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->default('Live teaching')->maxLength(80),
            TextInput::make('heading')->default('Upcoming webinars')->maxLength(160),
            Textarea::make('intro')->rows(2),
            TextInput::make('limit')->numeric()->default(2)->minValue(1)->maxValue(6),
        ];
    }

    public static function shouldRender(array $prepared, array $context): bool
    {
        return ($context['preview'] ?? false) || collect($prepared['webinars'] ?? [])->isNotEmpty();
    }

    public static function prepare(array $data): array
    {
        $data['webinars'] = Webinar::published()->where('starts_at', '>', now()->subMinutes(60))
            ->orderBy('starts_at')->limit((int) ($data['limit'] ?? 2))->get();

        return $data;
    }
}
