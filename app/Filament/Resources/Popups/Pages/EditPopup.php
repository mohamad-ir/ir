<?php

namespace App\Filament\Resources\Popups\Pages;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Resources\Popups\PopupResource;
use App\Filament\Resources\Popups\Widgets\PopupPerformance;
use App\Filament\Resources\Popups\Widgets\PopupStatsOverview;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPopup extends EditRecord
{
    use InteractsWithPopupPreview;
    use TranslatesPageLabels;

    protected static string $resource = PopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('Preview on the website'))->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => route('preview.popup', $this->record), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [PopupStatsOverview::class];
    }

    protected function getFooterWidgets(): array
    {
        return [PopupPerformance::class];
    }

    protected function afterSave(): void
    {
        $this->refreshPreview();
    }
}
