<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('New page'))->icon('heroicon-m-plus')];
    }
}
