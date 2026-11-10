<?php

namespace App\Filament\Resources\Media;

use App\Filament\Concerns\TranslatesResourceLabels;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Services\Media\MediaService;
use App\Services\Media\MediaUsage;
use App\Support\Ui;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MediaResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Media::class;

    protected static ?string $modelLabel = 'media item';

    protected static ?string $pluralModelLabel = 'Media library';

    protected static ?string $navigationLabel = 'Media library';

    protected static ?string $slug = 'media';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function folderSelect(): Select
    {
        return Select::make('folder_id')->label(__('Folder'))
            ->options(fn () => MediaFolder::orderBy('name')->pluck('name', 'id'))
            ->createOptionForm([TextInput::make('name')->required()->maxLength(80)])
            ->createOptionUsing(fn (array $data) => MediaFolder::create($data)->id)
            ->placeholder(__('No folder'))->searchable();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                View::make('filament.media.preview'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Details')->heading(__('Details'))->schema([
                    TextInput::make('title')->maxLength(250),
                    TextInput::make('alt')->label(__('Alternative text'))->maxLength(250)
                        ->helperText(__('Describe the image for screen readers and Google Images, e.g. “OCT B-scan showing subretinal fluid”.')),
                    Textarea::make('caption')->rows(3),
                    TextInput::make('credit')->maxLength(250)->placeholder(__('Courtesy of…')),
                    Textarea::make('description')->label(__('Internal notes'))->rows(2),
                    static::folderSelect(),
                    Toggle::make('is_deidentified')->label(__('Checked: contains no patient-identifiable information')),
                    Select::make('quality')->label(__('Image quality'))
                        ->visible(fn (?Media $record) => $record?->isImage() && $record->mime_type !== 'image/gif')
                        ->options(Media::qualityOptions())->default('auto')->selectablePlaceholder(false)
                        ->formatStateUsing(fn (?string $state) => $state ?: 'auto')
                        ->dehydrateStateUsing(fn (?string $state) => $state === 'auto' ? null : $state)
                        ->helperText(fn (?Media $record) => __('Automatic uses the high profile for clinical images (images in OCT cases and questions, de-identified images, grayscale OCT scans, PNG files) and the standard profile for photographs. The original file is always kept unchanged.')
                            .($record?->optimized_profile ? ' '.__('Applied: :profile.', ['profile' => Media::qualityOptions()[$record->optimized_profile] ?? $record->optimized_profile]) : '')),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->contentGrid(['md' => 3, 'xl' => 5, '2xl' => 6])
            ->paginated([24, 48, 96])
            ->columns([
                Stack::make([
                    ImageColumn::make('preview')->getStateUsing(fn (Media $record) => $record->isImage() ? $record->thumbUrl() : null)
                        ->defaultImageUrl(fn (Media $record) => 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 110"><rect width="160" height="110" fill="#e5e7eb"/><text x="80" y="62" text-anchor="middle" font-family="Arial" font-size="18" font-weight="700" fill="#6b7280">'.strtoupper($record->extension).'</text></svg>'))
                        ->imageHeight('9.5rem')->imageWidth('100%')->extraImgAttributes(['class' => 'w-full object-cover rounded-lg', 'style' => 'width:100%;height:9.5rem;object-fit:cover', 'loading' => 'lazy']),
                    TextColumn::make('title')->weight('medium')->limit(34)->searchable(['title', 'original_name', 'alt', 'caption'])->extraAttributes(['class' => 'mt-2']),
                    TextColumn::make('meta')->getStateUsing(fn (Media $record) => collect([$record->dimensions(), $record->humanSize()])->filter()->implode(' · '))->color('gray')->size('xs'),
                    TextColumn::make('alt_missing')->getStateUsing(fn (Media $record) => $record->isImage() && blank($record->alt) ? 'Missing alt text' : null)->badge()->color('warning')->size('xs'),
                ])->space(1),
            ])
            ->filters([
                SelectFilter::make('type')->options(Ui::options(['image' => 'Images', 'video' => 'Videos', 'application' => 'Documents']))
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->where('mime_type', 'like', $data['value'].'/%') : $query),
                SelectFilter::make('folder_id')->label(__('Folder'))->options(fn () => MediaFolder::orderBy('name')->pluck('name', 'id')),
                TernaryFilter::make('missing_alt')->label(__('Alt text'))->trueLabel(__('Missing alt text'))->falseLabel(__('Has alt text'))
                    ->queries(true: fn (Builder $query) => $query->where('mime_type', 'like', 'image/%')->where(fn ($w) => $w->whereNull('alt')->orWhere('alt', '')), false: fn (Builder $query) => $query->whereNotNull('alt')->where('alt', '!=', '')),
                TernaryFilter::make('is_deidentified')->label(__('De-identification checked')),
            ])
            ->recordActions([EditAction::make()->label(__('Details'))])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('move')->label(__('Move to folder'))->icon('heroicon-o-folder')
                        ->schema([static::folderSelect()])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['folder_id' => $data['folder_id']]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('quality')->label(__('Set image quality'))->icon('heroicon-o-sparkles')
                        ->schema([Select::make('quality')->label(__('Image quality'))->options(Media::qualityOptions())->default('auto')->required()
                            ->helperText(__('The optimised versions are rebuilt in the background within a few minutes. Originals are not changed.'))])
                        ->action(function (Collection $records, array $data) {
                            $images = $records->filter(fn (Media $m) => $m->isImage() && $m->mime_type !== 'image/gif');
                            Media::whereKey($images->modelKeys())->update(['quality' => $data['quality'] === 'auto' ? null : $data['quality'], 'variants_version' => 0]);
                            Notification::make()->success()->title(__(':count image(s) will be re-optimised', ['count' => $images->count()]))->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('delete')->label(__('Delete'))->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                        ->modalDescription(__('Files that are still used on the website will be skipped.'))
                        ->visible(fn () => auth()->user()->can('media.delete'))
                        ->action(function (Collection $records) {
                            $deleted = 0;
                            $skipped = 0;
                            foreach ($records as $media) {
                                if (app(MediaUsage::class)->find($media)) {
                                    $skipped++;

                                    continue;
                                }
                                app(MediaService::class)->delete($media);
                                $deleted++;
                            }
                            Notification::make()->success()->title("Deleted {$deleted} files".($skipped ? ", skipped {$skipped} in use" : ''))->send();
                        })->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
