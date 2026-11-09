<?php

namespace App\Filament\Resources\Topics\Pages;

use App\Filament\Resources\Topics\TopicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTopics extends ListRecords
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('New topic'))->icon('heroicon-m-plus')];
    }
}
