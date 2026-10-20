<?php

namespace App\Filament\Resources\Questions;

use App\Enums\Difficulty;
use App\Enums\Exam;
use App\Enums\QuestionType;
use App\Filament\Forms\MediaPicker;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\Question;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $modelLabel = 'exam question';

    protected static ?string $navigationLabel = 'Question bank';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Exam preparation';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make('Question')->schema([
                    ToggleButtons::make('type')->options(QuestionType::options())->default('sba')->inline()->live()->required(),
                    TextInput::make('title')->label('Short title')->required()->maxLength(200)->helperText('Shown in lists and search results — avoid revealing the answer.'),
                    Fields::richEditor('stem', 'Question stem')->required(),
                    MediaPicker::make('media_id')->label('Image')->helperText('Required for image-based questions.')->required(fn (Get $get) => $get('type') === 'image'),
                ]),
                Section::make('Answer options')->visible(fn (Get $get) => $get('type') !== 'viva')->schema([
                    Repeater::make('options')->hiddenLabel()->schema([
                        TextInput::make('text')->required()->columnSpan(4),
                        Toggle::make('is_correct')->label('Correct')->inline(false),
                    ])->columns(5)->reorderable()->minItems(fn (Get $get) => in_array($get('type'), ['sba', 'image']) ? 2 : 0)->maxItems(8)
                        ->defaultItems(fn (Get $get) => $get('type') === 'viva' ? 0 : 5)->addActionLabel('Add option')
                        ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                            $correct = collect($value)->where('is_correct', true)->count();
                            if (count($value ?? []) >= 2 && $correct !== 1) {
                                $fail('Mark exactly one option as correct.');
                            }
                        }]),
                ]),
                Section::make('Answer')->schema([
                    Fields::richEditor('model_answer', 'Model answer')->visible(fn (Get $get) => in_array($get('type'), ['viva', 'clinical'])),
                    Fields::richEditor('explanation', 'Explanation'),
                    Repeater::make('key_points')->label('Key exam points')->schema([Textarea::make('point')->hiddenLabel()->rows(2)->required()])->defaultItems(0)->addActionLabel('Add key point'),
                    Fields::references(),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('questions', hasFeatured: false),
                Section::make('Classification')->schema([
                    Select::make('exam_topic_id')->label('Exam topic')->relationship('examTopic', 'title')->preload()->searchable(),
                    Select::make('exam')->options(Exam::options())->default('all')->required(),
                    ToggleButtons::make('difficulty')->options(Difficulty::options())->default('intermediate')->inline(),
                    Fields::topics(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('examTopic'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(['title', 'stem'])->weight('medium')->wrap()->extraHeaderAttributes(['style' => 'min-width: 18rem'])->description(fn (Question $record) => $record->examTopic?->title),
                TextColumn::make('type')->badge(),
                TextColumn::make('exam')->badge()->color('gray')->toggleable(),
                ContentTable::status(),
                TextColumn::make('attempts_count')->label('Attempts')->numeric()->sortable()->alignEnd(),
                TextColumn::make('success')->label('Correct')->getStateUsing(fn (Question $record) => $record->successRate() !== null ? $record->successRate().'%' : '—')->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('type')->options(QuestionType::options()),
                SelectFilter::make('exam_topic_id')->label('Exam topic')->relationship('examTopic', 'title'),
                SelectFilter::make('exam')->options(Exam::options()),
                ...ContentTable::filters(topics: true),
            ])
            ->recordActions(ContentTable::recordActions('question', 'questions'))
            ->toolbarActions(ContentTable::bulkActions('questions'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
