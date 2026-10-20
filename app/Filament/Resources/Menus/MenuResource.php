<?php

namespace App\Filament\Resources\Menus;

use App\Models\Menu;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationLabel = 'Navigation menus';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|\UnitEnum|null $navigationGroup = 'Design';

    protected static ?int $navigationSort = 3;

    /** Quick-pick list of internal destinations. */
    public static function destinations(): array
    {
        $fixed = [
            '/' => 'Home', '/cases' => 'OCT case library', '/case-of-the-week' => 'Case of the Week', '/topics' => 'Topics A–Z',
            '/videos' => 'Video library', '/articles' => 'Articles', '/exam-prep' => 'Exam preparation', '/exam-prep/practice' => 'Question bank',
            '/search' => 'Search', '/newsletter' => 'Newsletter signup', '/contact' => 'Contact', '/login' => 'Sign in', '/register' => 'Create account',
        ];
        $pages = Page::published()->orderBy('title')->get()->mapWithKeys(fn (Page $p) => [$p->urlPath() => 'Page: '.$p->title])->all();

        return $fixed + $pages;
    }

    protected static function itemSchema(bool $withChildren): array
    {
        return array_values(array_filter([
            TextInput::make('label')->required()->maxLength(60),
            Select::make('quick')->label('Link to')->options(fn () => static::destinations())->searchable()->dehydrated(false)
                ->placeholder('Choose a page…')->live()->afterStateUpdated(fn (?string $state, Set $set) => $state ? $set('url', $state) : null),
            TextInput::make('url')->label('URL')->required()->maxLength(500)->placeholder('/cases or https://…'),
            TextInput::make('description')->maxLength(120)->placeholder('Optional — shown in dropdowns')->visible(! $withChildren),
            Toggle::make('new_tab')->label('Open in a new tab')->inline(false),
            $withChildren ? Repeater::make('children')->label('Dropdown items')->schema(static::itemSchema(false))->columns(2)
                ->collapsible()->collapsed()->reorderableWithDragAndDrop()->defaultItems(0)->addActionLabel('Add dropdown item')
                ->itemLabel(fn (array $state) => $state['label'] ?? null)->columnSpanFull() : null,
        ]));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(80)->helperText('Shown as the column heading for footer menus.'),
                Select::make('location')->options(Menu::LOCATIONS)->unique(ignoreRecord: true)->placeholder('Not displayed')
                    ->helperText('Where this menu appears on the website.'),
            ]),
            Repeater::make('items')->label('Menu items')->schema(static::itemSchema(true))->columns(2)
                ->reorderableWithDragAndDrop()->collapsible()->cloneable()->defaultItems(0)->addActionLabel('Add menu item')
                ->itemLabel(fn (array $state) => ($state['label'] ?? '').(! empty($state['children']) ? '  ▾ '.count($state['children']) : '')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->weight('medium'),
            TextColumn::make('location')->formatStateUsing(fn (?string $state) => Menu::LOCATIONS[$state] ?? '—')->badge()->color('gray'),
            TextColumn::make('items')->label('Items')->getStateUsing(fn (Menu $record) => count($record->items ?? [])),
            TextColumn::make('updated_at')->since(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
