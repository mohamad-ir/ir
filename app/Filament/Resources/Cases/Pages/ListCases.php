<?php

namespace App\Filament\Resources\Cases\Pages;

use App\Filament\Resources\Cases\CaseResource;
use App\Filament\Support\StatusTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListCases extends ListRecords
{
    use StatusTabs;

    protected static string $resource = CaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New case')->icon('heroicon-m-plus')];
    }

    public function getTabs(): array
    {
        return [
            ...$this->statusTabs(),
            'cotw' => Tab::make('Case of the Week')->icon('heroicon-m-sparkles')->modifyQueryUsing(fn ($query) => $query->where('is_case_of_week', true)->reorder()->orderByDesc('case_of_week_date')),
        ];
    }
}
