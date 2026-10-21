<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Support\StatusTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    use StatusTabs;

    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New course')->icon('heroicon-m-plus')];
    }

    public function getTabs(): array
    {
        return $this->statusTabs();
    }
}
