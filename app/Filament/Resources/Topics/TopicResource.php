<?php

namespace App\Filament\Resources\Topics;

use App\Filament\Forms\MediaPicker;
use App\Filament\Support\Fields;
use App\Models\Topic;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $navigationLabel = 'Topics & diseases';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->columns(2)->schema([
                    TextInput::make('name')->required()->maxLength(120)->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set, $get, $record) => ! $record ? $set('slug', str($state)->slug()->toString()) : null),
                    TextInput::make('abbreviation')->maxLength(40)->placeholder('e.g. CSR'),
                    TextInput::make('slug')->label('URL')->prefix(url('/topics').'/')->required()->unique(ignoreRecord: true)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->columnSpanFull(),
                    Select::make('parent_id')->label('Parent topic')->relationship('parent', 'name', fn (Builder $query, ?Topic $record) => $query->whereNull('parent_id')->when($record, fn ($q) => $q->whereKeyNot($record->id)))->placeholder('Top-level topic'),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Textarea::make('description')->rows(2)->columnSpanFull()->helperText('Shown on the topic page and in Google results.'),
                ]),
                Section::make('Topic page introduction')->collapsible()->schema([Fields::richEditor('body')->hiddenLabel()]),
                Fields::seo('name', 'description'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Display')->schema([
                    Toggle::make('is_featured')->label('Feature on homepage & search'),
                    ColorPicker::make('color'),
                    MediaPicker::make('media_id')->label('Icon image'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent')->withCount(['cases', 'articles', 'videos']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->searchable(['name', 'abbreviation'])->weight('medium')->description(fn (Topic $record) => $record->parent?->name),
                TextColumn::make('abbreviation')->badge()->color('gray'),
                TextColumn::make('cases_count')->label('Cases')->sortable(),
                TextColumn::make('articles_count')->label('Articles'),
                TextColumn::make('videos_count')->label('Videos'),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->filters([SelectFilter::make('parent_id')->label('Parent')->relationship('parent', 'name', fn (Builder $query) => $query->whereNull('parent_id'))])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->iconButton()->url(fn (Topic $record) => $record->url(), true),
                EditAction::make(), DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }
}
