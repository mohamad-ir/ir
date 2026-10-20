<?php

namespace App\Filament\Resources\Categories;

use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make()->columns(2)->schema([
            Select::make('type')->options(Category::TYPES)->required()->default('article'),
            TextInput::make('name')->required()->maxLength(100)->live(onBlur: true)->afterStateUpdated(fn ($state, $set, $record) => ! $record ? $set('slug', str($state)->slug()->toString()) : null),
            TextInput::make('slug')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, $get) => $rule->where('type', $get('type'))),
            TextInput::make('sort_order')->numeric()->default(0),
            Textarea::make('description')->rows(2)->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->reorderable('sort_order')->columns([
            TextColumn::make('name')->searchable()->weight('medium'),
            TextColumn::make('type')->formatStateUsing(fn ($state) => Category::TYPES[$state] ?? $state)->badge(),
            TextColumn::make('items')->label('Items')->getStateUsing(fn (Category $record) => $record->type === 'video' ? $record->videos()->count() : $record->articles()->count()),
        ])->filters([SelectFilter::make('type')->options(Category::TYPES)])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
