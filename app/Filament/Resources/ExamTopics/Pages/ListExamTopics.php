<?php

namespace App\Filament\Resources\ExamTopics\Pages;

use App\Filament\Resources\ExamTopics\ExamTopicResource;
use App\Filament\Support\StatusTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamTopics extends ListRecords
{
    use StatusTabs;

    protected static string $resource = ExamTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New topic')->icon('heroicon-m-plus')];
    }

    public function getTabs(): array
    {
        return $this->statusTabs();
    }
}
