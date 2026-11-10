<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Concerns\TranslatesPageLabels;
use App\Filament\Resources\Media\MediaResource;
use App\Services\Media\MediaService;
use App\Services\Media\MediaUsage;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditMedia extends EditRecord
{
    use TranslatesPageLabels;

    protected static string $resource = MediaResource::class;

    public function getTitle(): string
    {
        return $this->record->title ?: $this->record->original_name;
    }

    /** A different quality profile (or clinical flag) needs new optimised versions. */
    protected function afterSave(): void
    {
        if ($this->record->isImage() && $this->record->wasChanged(['quality', 'is_deidentified'])) {
            try {
                app(MediaService::class)->regenerate($this->record);
                Notification::make()->success()->title(__('Responsive sizes regenerated'))->send();
            } catch (Throwable $e) {
                Notification::make()->danger()->title(__('Could not regenerate the sizes'))->body($e->getMessage())->send();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('replace')->label(__('Replace file'))->icon('heroicon-o-arrow-path')->color('gray')
                ->modalDescription(__('Upload a new version. Every page using this item updates automatically and its URL is preserved where possible.'))
                ->schema([FileUpload::make('file')->required()->storeFiles(false)->acceptedFileTypes(MediaService::acceptedMimeTypes())->maxSize(204800)])
                ->action(function (array $data) {
                    try {
                        app(MediaService::class)->replace($this->record, $data['file']);
                        Notification::make()->success()->title(__('File replaced'))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('Could not replace the file'))->body($e->getMessage())->send();
                    }
                    $this->refreshFormData(['width', 'height']);
                }),
            Action::make('regenerate')->label(__('Regenerate sizes'))->icon('heroicon-o-arrows-pointing-out')->color('gray')
                ->visible(fn () => $this->record->isImage())
                ->action(function () {
                    try {
                        app(MediaService::class)->regenerate($this->record);
                        Notification::make()->success()->title(__('Responsive sizes regenerated'))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('Could not regenerate the sizes'))->body($e->getMessage())->send();
                    }
                }),
            Action::make('download')->label(__('Download'))->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->url(fn () => $this->record->url(), shouldOpenInNewTab: true),
            Action::make('delete')->label(__('Delete'))->icon('heroicon-o-trash')->color('danger')
                ->visible(fn () => auth()->user()->can('media.delete'))
                ->requiresConfirmation()
                ->modalDescription(function () {
                    $uses = app(MediaUsage::class)->find($this->record);

                    return $uses ? __('Warning: this file is used in :count place(s): :places. Deleting it will leave gaps on those pages.', ['count' => count($uses), 'places' => collect($uses)->take(5)->map(fn ($u) => __($u['type']).' “'.$u['title'].'”')->implode(', ')]) : __('This file is not used anywhere on the website.');
                })
                ->action(function () {
                    app(MediaService::class)->delete($this->record);
                    Notification::make()->success()->title(__('File deleted'))->send();
                    $this->redirect(MediaResource::getUrl('index'));
                }),
        ];
    }
}
