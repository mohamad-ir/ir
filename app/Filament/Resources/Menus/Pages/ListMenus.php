<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('New menu'))->icon('heroicon-m-plus')];
    }
}
