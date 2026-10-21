<?php

namespace App\Filament\Resources\ProfileEntries\Pages;

use App\Filament\Resources\ProfileEntries\ProfileEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProfileEntry extends EditRecord
{
    protected static string $resource = ProfileEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
