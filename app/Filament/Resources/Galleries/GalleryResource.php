<?php

namespace App\Filament\Resources\Galleries;

use App\Filament\Forms\MediaPicker;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\Gallery;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GalleryResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Gallery::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Photo galleries';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::title('/galleries'),
                    Fields::slug('/galleries', 'galleries'),
                    Textarea::make('summary')->rows(2)->maxLength(400)->helperText(__('Shown on the gallery card and in search results.')),
                    Fields::richEditor('description', 'Introduction (optional)'),
                ]),
                Section::make('Display')->heading(__('Display'))->icon('heroicon-o-squares-2x2')->columns(2)->schema([
                    ToggleButtons::make('layout')->options(\App\Support\Ui::options(Gallery::LAYOUTS))->default('justified')->columnSpanFull(),
                    Toggle::make('dark_viewer')->label(__('Dark background'))->helperText(__('Best for OCT and angiography images.')),
                    TextInput::make('sort_order')->label(__('Order on the galleries page'))->numeric()->default(0),
                ]),
                Fields::seo('title', 'summary'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('galleries'),
                Section::make('Details')->heading(__('Details'))->icon('heroicon-o-tag')->schema([
                    MediaPicker::make('cover_media_id')->label(__('Cover image'))->helperText(__('Leave empty to use the first image.')),
                    Fields::topics(),
                    Fields::tags(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['coverMedia', 'items.media'])->withCount('items'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->getStateUsing(fn (Gallery $record) => $record->cover()?->thumbUrl())->imageWidth(72)->imageHeight(54)->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('title')->searchable()->sortable()->weight('medium')->wrap()->description(fn (Gallery $record) => __(':count images', ['count' => $record->items_count])),
                ContentTable::status(),
                TextColumn::make('access_level')->label(__('Visible to'))->badge()->toggleable(),
                ContentTable::published(),
                ContentTable::views(),
            ])
            ->filters(ContentTable::filters())
            ->recordActions(ContentTable::recordActions('gallery', 'galleries'))
            ->toolbarActions(ContentTable::bulkActions('galleries'));
    }

    public static function getRelations(): array
    {
        return [RelationManagers\ImagesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }
}
