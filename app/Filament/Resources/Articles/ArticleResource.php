<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\Article;
use App\Models\Category;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::title('/articles'),
                    Fields::slug('/articles', 'articles'),
                    Textarea::make('excerpt')->rows(2)->maxLength(400)->helperText('Shown on cards, in the article header and as the default Google description.'),
                    Fields::richEditor('body', 'Article')->required()->extraInputAttributes(['style' => 'min-height: 28rem']),
                ]),
                Section::make('References')->icon('heroicon-o-book-open')->collapsible()->schema([Fields::references()->hiddenLabel()]),
                Fields::seo('title', 'excerpt'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('articles', hasComments: true),
                Section::make('Classification')->icon('heroicon-o-tag')->schema([
                    Select::make('category_id')->label('Category')->relationship('category', 'name', fn (Builder $query) => $query->where('type', 'article'))
                        ->createOptionForm([\Filament\Forms\Components\TextInput::make('name')->required()])
                        ->createOptionUsing(fn (array $data) => Category::create(['type' => 'article', 'name' => $data['name'], 'slug' => str($data['name'])->slug()])->id)
                        ->preload()->searchable(),
                    Fields::topics(),
                    Fields::tags(),
                ]),
                Section::make('Featured image')->icon('heroicon-o-photo')->schema([Fields::featuredImage()->hiddenLabel()]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['featuredMedia', 'category', 'author']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->getStateUsing(fn (Article $record) => $record->featuredMedia?->thumbUrl())->imageWidth(72)->imageHeight(48)->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('title')->searchable()->sortable()->weight('medium')->wrap()->extraHeaderAttributes(['style' => 'min-width: 20rem'])->description(fn (Article $record) => $record->category?->name),
                ContentTable::status(),
                ContentTable::published(),
                TextColumn::make('reading_time')->label('Read')->suffix(' min')->toggleable(isToggledHiddenByDefault: true),
                ContentTable::views(),
                ContentTable::author(),
            ])
            ->filters([...ContentTable::filters(), SelectFilter::make('category_id')->label('Category')->relationship('category', 'name', fn (Builder $query) => $query->where('type', 'article'))])
            ->recordActions(ContentTable::recordActions('article', 'articles'))
            ->toolbarActions(ContentTable::bulkActions('articles'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
