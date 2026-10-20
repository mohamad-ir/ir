<?php

namespace App\Filament\Resources\Modalities;

use App\Models\Modality;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModalityResource extends Resource
{
    protected static ?string $model = Modality::class;

    protected static ?string $navigationLabel = 'Imaging modalities';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make()->columns(2)->schema([
            TextInput::make('name')->required(),
            TextInput::make('abbreviation'),
            TextInput::make('slug')->required()->unique(ignoreRecord: true)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
            TextInput::make('sort_order')->numeric()->default(0),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->reorderable('sort_order')->columns([
            TextColumn::make('name')->weight('medium'),
            TextColumn::make('abbreviation')->badge()->color('gray'),
            TextColumn::make('cases_count')->counts('cases')->label('Cases'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModalities::route('/'),
            'create' => Pages\CreateModality::route('/create'),
            'edit' => Pages\EditModality::route('/{record}/edit'),
        ];
    }
}
