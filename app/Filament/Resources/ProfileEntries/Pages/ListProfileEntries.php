<?php

namespace App\Filament\Resources\ProfileEntries\Pages;

use App\Filament\Resources\ProfileEntries\ProfileEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProfileEntries extends ListRecords
{
    protected static string $resource = ProfileEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New entry')->icon('heroicon-m-plus')];
    }
}
