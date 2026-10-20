<?php

namespace App\Filament\Resources\ProfileEntries;

use App\Enums\ProfileEntryType;
use App\Models\ProfileEntry;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ProfileEntryResource extends Resource
{
    protected static ?string $model = ProfileEntry::class;

    protected static ?string $modelLabel = 'CV entry';

    protected static ?string $navigationLabel = 'Profile & CV';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        $isPub = fn (Get $get) => in_array($get('type'), ['publication', 'book']);

        return $schema->components([Section::make()->columns(2)->schema([
            Select::make('type')->options(ProfileEntryType::options())->required()->live()->columnSpanFull(),
            TextInput::make('title')->required()->maxLength(250)->columnSpanFull(),
            TextInput::make('subtitle')->label(fn (Get $get) => $isPub($get) ? 'Journal / publisher' : 'Institution / organisation')->maxLength(250),
            TextInput::make('location')->maxLength(250),
            TextInput::make('start_year')->label(fn (Get $get) => $isPub($get) ? 'Year' : 'From')->maxLength(10),
            TextInput::make('end_year')->label('To')->maxLength(10)->placeholder('Present')->hidden($isPub),
            TextInput::make('authors')->maxLength(1000)->columnSpanFull()->visible($isPub),
            TextInput::make('doi')->label('DOI')->visible($isPub),
            TextInput::make('pmid')->label('PubMed ID')->visible($isPub),
            TextInput::make('url')->label('Link')->url()->columnSpanFull(),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            Toggle::make('is_highlighted')->label('Highlight (e.g. key publication)'),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup(Group::make('type')->getTitleFromRecordUsing(fn (ProfileEntry $record) => $record->type->getLabel())->collapsible())
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->searchable(['title', 'subtitle', 'authors'])->weight('medium')->wrap()->description(fn (ProfileEntry $record) => $record->subtitle),
                TextColumn::make('period')->getStateUsing(fn (ProfileEntry $record) => $record->period()),
                IconColumn::make('is_highlighted')->label('Highlight')->boolean(),
            ])
            ->filters([SelectFilter::make('type')->options(ProfileEntryType::options())])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfileEntries::route('/'),
            'create' => Pages\CreateProfileEntry::route('/create'),
            'edit' => Pages\EditProfileEntry::route('/{record}/edit'),
        ];
    }
}
