<?php

namespace App\Filament\Resources\Pages;

use App\Enums\ContentStatus;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Page::class;

    protected static ?string $navigationLabel = 'Pages & page builder';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Design';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function builder(string $name = 'blocks', bool $includeReusable = true): Builder
    {
        return Builder::make($name)
            ->label(__('Page sections'))
            ->blocks(app(BlockRegistry::class)->filamentBlocks($includeReusable))
            ->blockPickerColumns(['default' => 2, 'lg' => 3])
            ->blockPickerWidth('4xl')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderableWithDragAndDrop()
            ->reorderableWithButtons()
            ->blockNumbers(false)
            ->addActionLabel(__('Add section'))
            ->addBetweenActionLabel('Insert section here')
            ->truncateBlockLabel(false)
            ->default([]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'xl' => 12])->components([
            Group::make()->columnSpan(['xl' => 5])->schema([
                Section::make()->schema([
                    Fields::title('/'),
                    Fields::slug('/', 'pages')->helperText(__('The homepage always lives at the site root regardless of its URL.')),
                    Grid::make(2)->schema([
                        ToggleButtons::make('status')->options(\App\Support\Ui::options([
                            'draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived',
                        ]))->colors(['draft' => 'gray', 'published' => 'success', 'archived' => 'danger'])->default('draft')->inline()->required(),
                        DateTimePicker::make('published_at')->label(__('Publish date'))->native(false)->seconds(false)->displayFormat('j M Y H:i'),
                    ]),
                    Toggle::make('show_title')->label(__('Show a page header with the title'))->default(true)->live(),
                    Textarea::make('summary')->rows(2)->helperText(__('Shown under the title and used as the default meta description.')),
                ]),
                static::builder(),
                Fields::seo('title', 'summary'),
            ]),
            Group::make()->columnSpan(['xl' => 7])->schema([
                View::make('filament.page-builder.preview'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $homeId = (int) Settings::get('site.homepage_id');

        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('medium')
                    ->description(fn (Page $record) => $record->urlPath()),
                IconColumn::make('home')->label(__('Homepage'))->getStateUsing(fn (Page $record) => $record->id === $homeId)->boolean()->trueIcon('heroicon-s-home')->falseIcon('')->trueColor('primary'),
                TextColumn::make('blocks')->label(__('Sections'))->getStateUsing(fn (Page $record) => count($record->blocks ?? [])),
                ContentTable::status(),
                ContentTable::views(),
                TextColumn::make('updated_at')->label(__('Updated'))->since()->sortable(),
            ])
            ->recordActions([
                ...ContentTable::recordActions('page', 'pages'),
                Action::make('homepage')->label(__('Set as homepage'))->icon('heroicon-o-home')->iconButton()->tooltip(__('Set as homepage'))
                    ->visible(fn (Page $record) => $record->id !== $homeId && auth()->user()->can('design.manage'))
                    ->requiresConfirmation()
                    ->action(function (Page $record) {
                        $record->update(['status' => ContentStatus::Published, 'published_at' => $record->published_at ?? now()]);
                        Settings::set('site.homepage_id', $record->id);
                        Notification::make()->success()->title(__('“:title” is now the homepage', ['title' => $record->title]))->send();
                    }),
            ])
            ->toolbarActions(ContentTable::bulkActions('pages'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
