<?php

namespace App\Filament\Support;

use App\Filament\Concerns\TranslatesPageLabels;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

abstract class ContentEditRecord extends EditRecord
{
    use InteractsWithContent;
    use TranslatesPageLabels;

    protected function getHeaderActions(): array
    {
        return [
            $this->previewAction(),
            $this->viewLiveAction(),
            $this->revisionsAction(),
            ActionGroup::make([
                DeleteAction::make()->label(__('Move to bin')),
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

    public function getSubheading(): Htmlable|string|null
    {
        if (! method_exists($this->record, 'displayStatus')) {
            return null;
        }

        // The date is isolated so it reads correctly inside the Persian (right-to-left) dashboard.
        return new HtmlString(e($this->record->displayStatus())
            .($this->record->published_at ? ' · <bdi>'.e($this->record->published_at->format('j M Y H:i')).'</bdi>' : ''));
    }
}
