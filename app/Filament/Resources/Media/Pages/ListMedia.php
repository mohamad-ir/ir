<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Services\Media\MediaService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')->label('Upload files')->icon('heroicon-m-arrow-up-tray')
                ->visible(fn () => auth()->user()->can('media.create'))
                ->modalHeading('Upload to the media library')
                ->modalDescription('Images are optimised automatically: metadata (EXIF, GPS) is removed and responsive WebP versions are created.')
                ->schema([
                    FileUpload::make('files')->multiple()->required()->storeFiles(false)
                        ->acceptedFileTypes(MediaService::acceptedMimeTypes())->maxSize(204800)->maxParallelUploads(3)
                        ->helperText('JPG, PNG, WebP, GIF, PDF, MP4, WebM or MOV.'),
                    MediaResource::folderSelect(),
                    Toggle::make('is_deidentified')->label('I confirm these files contain no patient-identifiable information')->accepted(),
                ])
                ->action(function (array $data) {
                    $ok = 0;
                    $errors = [];
                    foreach ((array) $data['files'] as $file) {
                        try {
                            app(MediaService::class)->store($file, ['folder_id' => $data['folder_id'] ?? null, 'is_deidentified' => true]);
                            $ok++;
                        } catch (Throwable $e) {
                            $errors[] = $file->getClientOriginalName().': '.$e->getMessage();
                        }
                    }
                    $n = Notification::make()->title("Uploaded {$ok} ".str('file')->plural($ok));
                    $errors ? $n->warning()->body(implode("\n", $errors))->persistent() : $n->success();
                    $n->send();
                }),
        ];
    }
}
