<?php

namespace App\Filament\Resources\SearchSynonyms;

use App\Models\SearchSynonym;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SearchSynonymResource extends Resource
{
    protected static ?string $model = SearchSynonym::class;

    protected static ?string $navigationLabel = 'Search dictionary';

    protected static ?string $modelLabel = 'search term';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static string|\UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make()->schema([
            TextInput::make('term')->required()->maxLength(100)->unique(ignoreRecord: true)->placeholder('csr'),
            Textarea::make('synonyms')->required()->rows(3)->placeholder('central serous chorioretinopathy, central serous retinopathy')
                ->helperText('Comma-separated. A search for any of these words also finds the others.'),
            Toggle::make('is_active')->default(true),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('term')->columns([
            TextColumn::make('term')->searchable()->weight('medium')->badge(),
            TextColumn::make('synonyms')->searchable()->wrap(),
            ToggleColumn::make('is_active')->label('Active'),
        ])->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSearchSynonyms::route('/'),
            'create' => Pages\CreateSearchSynonym::route('/create'),
            'edit' => Pages\EditSearchSynonym::route('/{record}/edit'),
        ];
    }
}
