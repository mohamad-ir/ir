<?php

namespace App\Filament\Resources\ReusableBlocks\Pages;

use App\Filament\Resources\ReusableBlocks\ReusableBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReusableBlocks extends ListRecords
{
    protected static string $resource = ReusableBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New reusable block')->icon('heroicon-m-plus')];
    }
}
