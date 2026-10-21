<?php

namespace App\Filament\Resources\Modalities\Pages;

use App\Filament\Resources\Modalities\ModalityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModality extends CreateRecord
{
    protected static string $resource = ModalityResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
