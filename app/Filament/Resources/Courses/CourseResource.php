<?php

namespace App\Filament\Resources\Courses;

use App\Enums\Difficulty;
use App\Filament\Forms\MediaPicker;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\Course;
use App\Support\Money;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::title('/courses'),
                    Fields::slug('/courses', 'courses'),
                    TextInput::make('subtitle')->maxLength(250)->helperText(__('One line under the title, e.g. “A layer-by-layer method for reading any macular OCT”.')),
                    Textarea::make('summary')->rows(2)->maxLength(500)->helperText(__('Shown on course cards and in search results.')),
                ]),
                Section::make('Course page')->heading(__('Course page'))->icon('heroicon-o-document-text')->schema([
                    Repeater::make('outcomes')->label(__('What students will learn'))->schema([TextInput::make('text')->hiddenLabel()->required()->maxLength(200)])
                        ->defaultItems(0)->reorderable()->addActionLabel(__('Add learning outcome')),
                    Fields::richEditor('description', 'About this course'),
                    TextInput::make('trailer_url')->label(__('Introduction video (YouTube or Vimeo link)'))->url()->maxLength(500),
                    Repeater::make('faqs')->label(__('Questions & answers'))->schema([
                        TextInput::make('question')->required(),
                        Textarea::make('answer')->rows(2)->required(),
                    ])->defaultItems(0)->collapsible()->itemLabel(fn (array $state) => $state['question'] ?? null)->addActionLabel(__('Add question')),
                ]),
                Fields::seo('title', 'summary'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('courses', hasAccess: false),
                Section::make('Price & access')->heading(__('Price & access'))->icon('heroicon-o-banknotes')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('price')->label(__('Price'))->numeric()->minValue(0)->step('0.01')->default(0)->required()
                            ->helperText(__('0 = free course (account required).'))
                            ->formatStateUsing(fn ($state) => Money::toMajor($state ?? 0))
                            ->dehydrateStateUsing(fn ($state) => Money::toMinor($state)),
                        Select::make('currency')->options(array_combine(array_keys(Money::CURRENCIES), array_keys(Money::CURRENCIES)))
                            ->default(fn () => Settings::get('payments.currency', 'EUR'))->selectablePlaceholder(false)->required(),
                    ]),
                    Fields::otherPrices()->visible(fn ($get) => (float) $get('price') > 0),
                    Toggle::make('included_in_membership')->label(__('Included in membership'))->default(true)
                        ->helperText(__('Members get full access while their membership is active. Others can buy the course.')),
                ]),
                Section::make('Certificate')->heading(__('Certificate'))->icon('heroicon-o-trophy')->schema([
                    Toggle::make('certificate_enabled')->label(__('Award a certificate of completion'))->default(true)
                        ->helperText(__('Issued automatically when every lesson is complete (and every lesson quiz passed). Learners receive it by email as a PDF.')),
                    TextInput::make('cpd_hours')->label(__('Hours shown on the certificate'))->numeric()->minValue(0.5)->maxValue(500)->step('0.5')
                        ->helperText(__('Leave empty to use the total lesson duration.')),
                ]),
                Section::make('Details')->heading(__('Details'))->icon('heroicon-o-tag')->schema([
                    MediaPicker::make('featured_media_id')->label(__('Cover image')),
                    ToggleButtons::make('level')->options(Difficulty::options())->default('intermediate')->inline(),
                    Fields::topics(),
                    Fields::tags(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['featuredMedia'])->withCount('lessons'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->getStateUsing(fn (Course $record) => $record->featuredMedia?->thumbUrl())->imageWidth(80)->imageHeight(45)->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('title')->searchable()->sortable()->weight('medium')->wrap()->description(fn (Course $record) => __(':count lessons', ['count' => $record->lessons_count])),
                TextColumn::make('price')->label(__('Price'))->formatStateUsing(fn (Course $record) => $record->priceLabel())->sortable()
                    ->description(fn (Course $record) => $record->included_in_membership ? 'In membership' : null),
                ContentTable::status(),
                TextColumn::make('enrollments_count')->label(__('Students'))->numeric()->sortable()->alignEnd(),
                ContentTable::published(),
                ContentTable::views(),
            ])
            ->filters(ContentTable::filters())
            ->recordActions(ContentTable::recordActions('course', 'courses'))
            ->toolbarActions(ContentTable::bulkActions('courses'));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LessonsRelationManager::class,
            RelationManagers\SectionsRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
