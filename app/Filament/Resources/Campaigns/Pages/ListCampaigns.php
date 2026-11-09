<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('New'))->icon('heroicon-m-plus')];
    }
}
