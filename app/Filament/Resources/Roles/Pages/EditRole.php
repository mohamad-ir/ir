<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterSave(): void
    {
        $state = $this->form->getRawState();
        $permissions = collect($state)->filter(fn ($v, $k) => str_starts_with($k, 'permissions_'))->flatten()->filter()->values()->all();
        $this->record->syncPermissions($permissions);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        \App\Models\ActivityLog::record('permissions', 'Permissions updated for role '.$this->record->name, $this->record);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->hidden(fn () => array_key_exists($this->record->name, \App\Support\Permissions::ROLES))];
    }

    public function getTitle(): string
    {
        return 'Role: '.(\App\Support\Permissions::ROLES[$this->record->name] ?? $this->record->name);
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
