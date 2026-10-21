<?php

namespace App\Filament\Resources\MembershipPlans\Pages;

use App\Filament\Resources\MembershipPlans\MembershipPlanResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMembershipPlans extends ListRecords
{
    protected static string $resource = MembershipPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label('View pricing page')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(route('membership'), shouldOpenInNewTab: true),
            CreateAction::make()->label('New plan')->icon('heroicon-m-plus'),
        ];
    }
}
