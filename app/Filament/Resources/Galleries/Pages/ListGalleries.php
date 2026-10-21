<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Support\StatusTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGalleries extends ListRecords
{
    use StatusTabs;

    protected static string $resource = GalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New gallery')->icon('heroicon-m-plus')];
    }

    public function getTabs(): array
    {
        return $this->statusTabs();
    }
}
