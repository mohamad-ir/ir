<?php

namespace App\Filament\Resources\Webinars\Pages;

use App\Filament\Resources\Webinars\WebinarResource;
use App\Filament\Support\StatusTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebinars extends ListRecords
{
    use StatusTabs;

    protected static string $resource = WebinarResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New webinar')->icon('heroicon-m-plus')];
    }

    public function getTabs(): array
    {
        return $this->statusTabs();
    }
}
