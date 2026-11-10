<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use TranslatesPageLabels;

    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        RoleResource::savePermissions($this->record, $this->form->getRawState());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
