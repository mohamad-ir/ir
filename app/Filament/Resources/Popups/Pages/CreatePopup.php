<?php

namespace App\Filament\Resources\Popups\Pages;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Resources\Popups\PopupResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePopup extends CreateRecord
{
    use InteractsWithPopupPreview;
    use TranslatesPageLabels;

    protected static string $resource = PopupResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
