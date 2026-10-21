<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\AnalyticsQuery;
use Filament\Widgets\Widget;

class PopularContent extends Widget
{
    protected string $view = 'filament.widgets.popular';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 4];

    public static function canView(): bool
    {
        return auth()->user()?->can('analytics.view') ?? false;
    }

    public function getViewData(): array
    {
        return ['pages' => AnalyticsQuery::for(30)->topPages(6)];
    }
}
