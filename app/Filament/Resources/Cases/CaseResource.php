<?php

namespace App\Filament\Resources\Cases;

use App\Enums\Difficulty;
use App\Filament\Forms\MediaPicker;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\ClinicalCase;
use App\Models\Modality;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CaseResource extends Resource
{
    protected static ?string $model = ClinicalCase::class;

    protected static ?string $modelLabel = 'OCT case';

    protected static ?string $pluralModelLabel = 'OCT cases';

    protected static ?string $slug = 'cases';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-eye';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'diagnosis', 'summary'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter(['Diagnosis' => $record->diagnosis, 'Status' => $record->displayStatus()]);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = ClinicalCase::where('status', 'pending')->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Awaiting review';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::title('/cases'),
                    Fields::slug('/cases', 'clinical_cases'),
                    Textarea::make('summary')->rows(2)->maxLength(400)->helperText('One or two sentences shown on case cards and in Google results. Avoid giving away the diagnosis.'),
                ]),
                Tabs::make('case')->persistTabInQueryString()->tabs([
                    Tab::make('Presentation')->icon('heroicon-o-user')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('patient_age')->label('Age')->placeholder('e.g. 38 years'),
                            Select::make('patient_sex')->label('Sex')->options(['Female' => 'Female', 'Male' => 'Male', 'Other' => 'Other / not stated']),
                            ToggleButtons::make('eye')->inline()->options(['OD' => 'Right (OD)', 'OS' => 'Left (OS)', 'OU' => 'Both (OU)']),
                        ]),
                        TextInput::make('presenting_complaint')->maxLength(250)->placeholder('Two weeks of blurred central vision…'),
                        Fields::richEditor('history', 'Short history & examination'),
                    ]),
                    Tab::make('Imaging')->icon('heroicon-o-photo')->badge(fn (Get $get) => count($get('images') ?? []) ?: null)->schema([
                        Repeater::make('images')->label('Case images')
                            ->schema([
                                MediaPicker::make('media_id')->label('Image')->required()->columnSpan(2),
                                TextInput::make('label')->placeholder('OCT B-scan, OCT-A, FAF…')->maxLength(60),
                                Textarea::make('caption')->rows(2)->columnSpanFull(),
                            ])
                            ->columns(3)->reorderableWithDragAndDrop()->collapsible()->cloneable()
                            ->itemLabel(fn (array $state) => $state['label'] ?? null)
                            ->addActionLabel('Add image')
                            ->helperText('Upload de-identified images only. EXIF and location metadata are removed automatically.'),
                        Section::make('Before / after comparison')->description('Optional slider comparing two scans, e.g. before and after treatment.')->collapsible()->collapsed()
                            ->statePath('comparison')->columns(2)->schema([
                                MediaPicker::make('before_media_id')->label('Before'),
                                MediaPicker::make('after_media_id')->label('After'),
                                TextInput::make('before_label')->default('Before'),
                                TextInput::make('after_label')->default('After'),
                                TextInput::make('caption')->columnSpanFull(),
                            ]),
                        CheckboxList::make('modalities')->label('Imaging modalities')->relationship('modalities', 'name')->columns(3),
                    ]),
                    Tab::make('Diagnostic challenge')->icon('heroicon-o-question-mark-circle')->schema([
                        TextInput::make('quiz_prompt')->label('Question')->placeholder('What is the most likely diagnosis?'),
                        Repeater::make('quiz_options')->label('Answer options')
                            ->schema([
                                TextInput::make('text')->required()->columnSpan(3),
                                Toggle::make('is_correct')->label('Correct')->inline(false),
                            ])->columns(4)->reorderable()->maxItems(6)->defaultItems(0)->addActionLabel('Add option')
                            ->helperText('Add 2–6 options and mark the correct one. Leave empty to show a simple “Reveal the answer” button instead.'),
                    ]),
                    Tab::make('Answer & discussion')->icon('heroicon-o-light-bulb')->schema([
                        TextInput::make('diagnosis')->required()->maxLength(250),
                        Fields::richEditor('diagnosis_explanation', 'Why this diagnosis'),
                        Fields::richEditor('findings', 'Imaging findings'),
                        Repeater::make('differentials')->label('Differential diagnosis')->schema([
                            TextInput::make('name')->required(),
                            Textarea::make('features')->label('Distinguishing features')->rows(2),
                        ])->columns(2)->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['name'] ?? null)->addActionLabel('Add differential')->defaultItems(0),
                        Fields::richEditor('discussion', 'Discussion'),
                        Fields::richEditor('management', 'Management & outcome'),
                    ]),
                    Tab::make('Teaching points & references')->icon('heroicon-o-academic-cap')->schema([
                        Repeater::make('teaching_points')->label('Teaching points / clinical pearls')
                            ->schema([Textarea::make('point')->hiddenLabel()->required()->rows(2)])
                            ->reorderable()->addActionLabel('Add teaching point')->defaultItems(0),
                        Fields::references(),
                    ]),
                ]),
                Fields::seo('title', 'summary'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('cases'),
                Section::make('Case of the Week')->icon('heroicon-o-sparkles')->schema([
                    Toggle::make('is_case_of_week')->label('Feature as Case of the Week')->live(),
                    DatePicker::make('case_of_week_date')->label('Week starting')->native(false)->displayFormat('j M Y')
                        ->visible(fn (Get $get) => $get('is_case_of_week'))
                        ->helperText('The most recent date that has arrived becomes the current Case of the Week — schedule several weeks ahead.'),
                ]),
                Section::make('Classification')->icon('heroicon-o-tag')->schema([
                    Fields::topics(),
                    ToggleButtons::make('difficulty')->options(Difficulty::options())->colors(['beginner' => 'success', 'intermediate' => 'warning', 'advanced' => 'danger'])->default('intermediate')->inline(),
                    Fields::tags(),
                ]),
                Section::make('Cover image')->icon('heroicon-o-photo')->schema([
                    Fields::featuredImage()->hiddenLabel()->helperText('Defaults to the first case image.'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['featuredMedia', 'topics', 'author']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->getStateUsing(fn (ClinicalCase $record) => $record->coverImage()?->thumbUrl())->imageWidth(72)->imageHeight(48)->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('title')->searchable(['title', 'diagnosis'])->sortable()->weight('medium')->wrap()->extraHeaderAttributes(['style' => 'min-width: 20rem'])
                    ->description(fn (ClinicalCase $record) => $record->diagnosis),
                TextColumn::make('topics.name')->label('Topics')->badge()->color('gray')->limitList(2)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('difficulty')->badge()->toggleable(),
                IconColumn::make('is_case_of_week')->label('CotW')->boolean()->trueIcon('heroicon-s-sparkles')->falseIcon('')->trueColor('warning')->tooltip(fn (ClinicalCase $record) => $record->case_of_week_date?->format('j M Y'))->toggleable(),
                ContentTable::status(),
                ContentTable::published(),
                ContentTable::views(),
                TextColumn::make('reveals_count')->label('Reveals')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('success')->label('Correct')->getStateUsing(fn (ClinicalCase $record) => $record->guesses_count ? round($record->correct_guesses_count / $record->guesses_count * 100).'%' : '—')->toggleable(isToggledHiddenByDefault: true),
                ContentTable::author(),
                TextColumn::make('updated_at')->label('Updated')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ...ContentTable::filters(),
                SelectFilter::make('difficulty')->options(Difficulty::options()),
                SelectFilter::make('modality')->options(fn () => Modality::pluck('name', 'id'))->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereHas('modalities', fn ($m) => $m->whereKey($data['value'])) : $query),
                TernaryFilter::make('is_case_of_week')->label('Case of the Week'),
            ])
            ->recordActions(ContentTable::recordActions('case', 'cases'))
            ->toolbarActions(ContentTable::bulkActions('cases'))
            ->emptyStateHeading('No cases yet')
            ->emptyStateDescription('Create your first OCT teaching case.')
            ->emptyStateIcon('heroicon-o-eye');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCases::route('/'),
            'create' => Pages\CreateCase::route('/create'),
            'edit' => Pages\EditCase::route('/{record}/edit'),
        ];
    }
}
