<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static ?string $title = 'Dashboard';

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 12];
    }
}
