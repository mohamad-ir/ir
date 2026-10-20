<?php

namespace App\Filament\Resources\Media\Pages;

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
    protected static string $resource = MediaResource::class;

    public function getTitle(): string
    {
        return $this->record->title ?: $this->record->original_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('replace')->label('Replace file')->icon('heroicon-o-arrow-path')->color('gray')
                ->modalDescription('Upload a new version. Every page using this item updates automatically and its URL is preserved where possible.')
                ->schema([FileUpload::make('file')->required()->storeFiles(false)->acceptedFileTypes(MediaService::acceptedMimeTypes())->maxSize(204800)])
                ->action(function (array $data) {
                    try {
                        app(MediaService::class)->replace($this->record, $data['file']);
                        Notification::make()->success()->title('File replaced')->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Could not replace the file')->body($e->getMessage())->send();
                    }
                    $this->refreshFormData(['width', 'height']);
                }),
            Action::make('regenerate')->label('Regenerate sizes')->icon('heroicon-o-arrows-pointing-out')->color('gray')
                ->visible(fn () => $this->record->isImage())
                ->action(function () {
                    app(MediaService::class)->regenerate($this->record);
                    Notification::make()->success()->title('Responsive sizes regenerated')->send();
                }),
            Action::make('download')->label('Download')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->url(fn () => $this->record->url(), shouldOpenInNewTab: true),
            Action::make('delete')->label('Delete')->icon('heroicon-o-trash')->color('danger')
                ->visible(fn () => auth()->user()->can('media.delete'))
                ->requiresConfirmation()
                ->modalDescription(function () {
                    $uses = app(MediaUsage::class)->find($this->record);

                    return $uses ? 'Warning: this file is used in '.count($uses).' place(s): '.collect($uses)->take(5)->map(fn ($u) => $u['type'].' “'.$u['title'].'”')->implode(', ').'. Deleting it will leave gaps on those pages.' : 'This file is not used anywhere on the website.';
                })
                ->action(function () {
                    app(MediaService::class)->delete($this->record);
                    Notification::make()->success()->title('File deleted')->send();
                    $this->redirect(MediaResource::getUrl('index'));
                }),
        ];
    }
}
