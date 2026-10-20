<?php

namespace App\Filament\Resources\Modalities\Pages;

use App\Filament\Resources\Modalities\ModalityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditModality extends EditRecord
{
    protected static string $resource = ModalityResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
