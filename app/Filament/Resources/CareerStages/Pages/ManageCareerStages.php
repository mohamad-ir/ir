<?php

namespace App\Filament\Resources\CareerStages\Pages;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Resources\CareerStages\CareerStageResource;
use App\Models\CareerStage;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCareerStages extends ManageRecords
{
    use TranslatesPageLabels;

    protected static string $resource = CareerStageResource::class;

    public function getSubheading(): ?string
    {
        return __('The options members choose from (“I am a…”) when they register and in their profile. Drag to change the order.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('New career stage'))->icon('heroicon-m-plus')
                ->mutateDataUsing(function (array $data) {
                    $data['sort_order'] = (int) CareerStage::max('sort_order') + 10;

                    return $data;
                }),
        ];
    }
}
