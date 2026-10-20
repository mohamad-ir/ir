<?php

namespace App\Filament\Resources\ExamTopics;

use App\Enums\Exam;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\ExamTopic;
use App\Services\PageBuilder\Blocks\FeatureGridBlock;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamTopicResource extends Resource
{
    protected static ?string $model = ExamTopic::class;

    protected static ?string $modelLabel = 'exam topic';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Exam preparation';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::title('/exam-prep'),
                    Fields::slug('/exam-prep', 'exam_topics'),
                    Textarea::make('summary')->rows(2),
                    Fields::richEditor('body', 'Revision notes & exam tips')->extraInputAttributes(['style' => 'min-height: 24rem']),
                ]),
                Fields::seo('title', 'summary'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('exam_topics', hasAuthor: false, hasAccess: false, hasFeatured: false),
                Section::make('Details')->schema([
                    Select::make('exam')->options(Exam::options())->default('all')->required(),
                    Select::make('icon')->options(FeatureGridBlock::ICONS + ['scissors' => 'Surgery'])->searchable(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->weight('medium'),
                TextColumn::make('exam')->badge(),
                TextColumn::make('questions_count')->counts('questions')->label('Questions'),
                ContentTable::status(),
            ])
            ->recordActions(ContentTable::recordActions('exam_topic', 'exam_topics'))
            ->toolbarActions(ContentTable::bulkActions('exam_topics'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamTopics::route('/'),
            'create' => Pages\CreateExamTopic::route('/create'),
            'edit' => Pages\EditExamTopic::route('/{record}/edit'),
        ];
    }
}
