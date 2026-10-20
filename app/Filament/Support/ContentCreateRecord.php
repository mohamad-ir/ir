<?php

namespace App\Filament\Support;

use Filament\Resources\Pages\CreateRecord;

abstract class ContentCreateRecord extends CreateRecord
{
    use InteractsWithContent;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] ??= auth()->id();

        return $this->enforcePublishingPermission($data);
    }

    protected function afterCreate(): void
    {
        $this->reindex();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
