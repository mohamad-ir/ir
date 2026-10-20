<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Widgets\Widget;

class RecentActivity extends Widget
{
    protected string $view = 'filament.widgets.activity';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 4];

    public static function canView(): bool
    {
        return auth()->user()?->can('activity.view') ?? false;
    }

    public function getViewData(): array
    {
        return ['logs' => ActivityLog::with('user')->where('event', '!=', 'login')->latest('id')->limit(7)->get()];
    }
}
