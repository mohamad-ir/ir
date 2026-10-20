<?php

namespace App\Filament\Support;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

abstract class ContentEditRecord extends EditRecord
{
    use InteractsWithContent;

    protected function getHeaderActions(): array
    {
        return [
            $this->previewAction(),
            $this->viewLiveAction(),
            $this->revisionsAction(),
            ActionGroup::make([
                DeleteAction::make()->label('Move to bin'),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->enforcePublishingPermission($data);
    }

    protected function afterSave(): void
    {
        $this->reindex();
    }

    public function getSubheading(): ?string
    {
        return method_exists($this->record, 'displayStatus') ? $this->record->displayStatus().($this->record->published_at ? ' · '.$this->record->published_at->format('j M Y H:i') : '') : null;
    }
}
