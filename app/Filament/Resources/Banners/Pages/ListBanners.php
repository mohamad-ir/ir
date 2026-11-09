<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBanners extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('New banner'))->icon('heroicon-m-plus')];
    }
}
