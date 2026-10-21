<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\TrafficChart;

class TrafficOverview extends TrafficChart
{
    protected static bool $isDiscovered = true;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 8];

    protected ?string $heading = 'Traffic — last 30 days';

    public static function canView(): bool
    {
        return auth()->user()?->can('analytics.view') ?? false;
    }
}
