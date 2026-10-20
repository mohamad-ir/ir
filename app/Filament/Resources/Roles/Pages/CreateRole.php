<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();
        $permissions = collect($state)->filter(fn ($v, $k) => str_starts_with($k, 'permissions_'))->flatten()->filter()->values()->all();
        $this->record->syncPermissions($permissions);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        \App\Models\ActivityLog::record('permissions', 'Permissions updated for role '.$this->record->name, $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
