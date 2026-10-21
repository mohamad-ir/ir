<?php

namespace App\Filament\Resources\ProfileEntries\Pages;

use App\Filament\Resources\ProfileEntries\ProfileEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProfileEntry extends CreateRecord
{
    protected static string $resource = ProfileEntryResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
