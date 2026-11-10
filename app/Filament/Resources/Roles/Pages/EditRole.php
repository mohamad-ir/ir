<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Resources\Roles\RoleResource;
use App\Support\Permissions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    use TranslatesPageLabels;

    protected static string $resource = RoleResource::class;

    protected function afterSave(): void
    {
        RoleResource::savePermissions($this->record, $this->form->getRawState());
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->hidden(fn () => array_key_exists($this->record->name, Permissions::ROLES)
            || (! auth()->user()->isSuperAdmin() && $this->record->permissions->pluck('name')->intersect(Permissions::SUPER_ADMIN_ONLY)->isNotEmpty()))];
    }

    public function getTitle(): string
    {
        return __('Role').': '.__(Permissions::ROLES[$this->record->name] ?? $this->record->name);
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
