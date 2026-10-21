<?php

namespace App\Filament\Resources\Galleries\RelationManagers;

use App\Models\GalleryItem;
use App\Models\Media;
use App\Services\Media\MediaService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Images';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-photo';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->items()->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Textarea::make('caption')->rows(3)->maxLength(1000)->helperText('Leave empty to use the caption saved in the media library.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated([24, 48, 96])
            ->defaultPaginationPageOption(48)
            ->columns([
                ImageColumn::make('preview')->label('')->getStateUsing(fn (GalleryItem $record) => $record->media?->thumbUrl())->imageWidth(96)->imageHeight(72)->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('caption')->wrap()->limit(120)->placeholder('No caption')
                    ->getStateUsing(fn (GalleryItem $record) => $record->captionText())
                    ->description(fn (GalleryItem $record) => $record->media ? $record->media->original_name.' · '.$record->media->dimensions() : null),
                TextColumn::make('media.alt')->label('Alt text')->placeholder('Missing — add in Media library')->toggleable()->limit(50),
            ])
            ->headerActions([
                Action::make('upload')->label('Upload images')->icon('heroicon-m-arrow-up-tray')
                    ->modalDescription('Select or drop many images at once. Location data is removed automatically and web-optimised versions are created.')
                    ->schema([
                        FileUpload::make('files')->label('Images')->multiple()->required()->storeFiles(false)->maxFiles(60)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(25600)->panelLayout('grid'),
                        TextInput::make('credit')->label('Credit for all images (optional)'),
                        Toggle::make('deidentified')->label('I confirm these images contain no patient-identifiable information')->accepted()->default(false),
                    ])
                    ->action(function (array $data) {
                        $gallery = $this->getOwnerRecord();
                        $position = (int) $gallery->items()->max('sort_order');
                        $added = 0;
                        foreach ((array) $data['files'] as $file) {
                            try {
                                $media = app(MediaService::class)->store($file, array_filter(['credit' => $data['credit'] ?? null, 'is_deidentified' => true, 'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)]));
                                $gallery->items()->create(['media_id' => $media->id, 'sort_order' => ++$position]);
                                $added++;
                            } catch (Throwable $e) {
                                Notification::make()->danger()->title('Could not add '.$file->getClientOriginalName())->body($e->getMessage())->send();
                            }
                        }
                        Notification::make()->success()->title("{$added} images added")->send();
                    }),
                Action::make('library')->label('Add from media library')->icon('heroicon-m-photo')->color('gray')
                    ->schema([
                        Select::make('media_ids')->label('Images')->multiple()->searchable()->required()
                            ->options(fn () => Media::where('mime_type', 'like', 'image/%')->latest('id')->limit(200)->get()->mapWithKeys(fn (Media $m) => [$m->id => $m->title ?: $m->original_name]))
                            ->getSearchResultsUsing(fn (string $search) => Media::where('mime_type', 'like', 'image/%')->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('original_name', 'like', "%{$search}%"))->limit(50)->get()->mapWithKeys(fn (Media $m) => [$m->id => $m->title ?: $m->original_name])),
                    ])
                    ->action(function (array $data) {
                        $gallery = $this->getOwnerRecord();
                        $position = (int) $gallery->items()->max('sort_order');
                        foreach ($data['media_ids'] as $id) {
                            $gallery->items()->create(['media_id' => $id, 'sort_order' => ++$position]);
                        }
                        Notification::make()->success()->title(count($data['media_ids']).' images added')->send();
                    }),
            ])
            ->recordActions([
                Action::make('cover')->label('Use as cover')->icon('heroicon-o-star')->iconButton()
                    ->action(function (GalleryItem $record) {
                        $this->getOwnerRecord()->update(['cover_media_id' => $record->media_id]);
                        Notification::make()->success()->title('Cover updated')->send();
                    }),
                EditAction::make()->label('Caption')->iconButton(),
                DeleteAction::make()->label('Remove from gallery')->iconButton()->modalDescription('The image stays in the media library.'),
            ])
            ->toolbarActions([DeleteBulkAction::make()->label('Remove from gallery')])
            ->emptyStateHeading('No images yet')
            ->emptyStateDescription('Use “Upload images” to add many photos at once. Drag rows to change the order.');
    }
}
