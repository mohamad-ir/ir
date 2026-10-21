<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Enums\VideoProvider;
use App\Filament\Forms\MediaPicker;
use App\Filament\Support\Fields;
use App\Models\Lesson;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'Lessons';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-play-circle';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->lessons()->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(3)->schema([
                TextInput::make('title')->required()->maxLength(200)->columnSpan(2),
                Select::make('course_section_id')->label('Section')
                    ->relationship('section', 'title', fn ($query) => $query->where('course_id', $this->getOwnerRecord()->id)->orderBy('sort_order'))
                    ->createOptionForm([TextInput::make('title')->required()])
                    ->createOptionUsing(fn (array $data) => $this->getOwnerRecord()->sections()->create(['title' => $data['title'], 'sort_order' => $this->getOwnerRecord()->sections()->count()])->id)
                    ->placeholder('No section'),
            ]),
            Grid::make(3)->schema([
                TextInput::make('slug')->label('URL')->maxLength(100)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->helperText('Leave empty to create it from the title.')
                    ->unique('lessons', 'slug', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('course_id', $this->getOwnerRecord()->id)),
                TextInput::make('duration_minutes')->label('Duration (minutes)')->numeric()->minValue(0),
                Toggle::make('is_preview')->label('Free preview')->helperText('Anyone can watch this lesson.')->inline(false),
            ]),
            Textarea::make('summary')->rows(2)->maxLength(400),
            Section::make('Video')->compact()->schema([
                ToggleButtons::make('video_provider')->label('Source')->options(['' => 'No video'] + VideoProvider::options())->inline()->live()->default('vimeo')
                    ->formatStateUsing(fn ($state) => $state instanceof VideoProvider ? $state->value : ($state ?? ''))
                    ->dehydrateStateUsing(fn ($state) => $state ?: null),
                TextInput::make('video_url')->label('Video link')->url()->maxLength(500)
                    ->visible(fn (Get $get) => in_array($get('video_provider'), ['youtube', 'vimeo', 'url'], true))
                    ->required(fn (Get $get) => in_array($get('video_provider'), ['youtube', 'vimeo', 'url'], true))
                    ->helperText('For paid lessons use Vimeo with “Hide from Vimeo” + domain-level privacy, or an unlisted YouTube video. The link is only shown to students with access.'),
                MediaPicker::make('video_media_id')->label('Video file')->videosOnly()
                    ->visible(fn (Get $get) => $get('video_provider') === 'file')->required(fn (Get $get) => $get('video_provider') === 'file'),
            ]),
            Fields::richEditor('body', 'Lesson notes'),
            Select::make('attachments')->label('Downloads (PDF, slides…)')->multiple()->searchable()
                ->options(fn () => \App\Models\Media::where('mime_type', 'not like', 'video/%')->latest('id')->limit(100)->get()->mapWithKeys(fn ($m) => [$m->id => $m->title ?: $m->original_name]))
                ->helperText('Upload files in Library › Media first. Note: downloads are stored in the public media folder.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn ($query) => $query->with('section'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->weight('medium')->searchable()->description(fn (Lesson $record) => $record->section?->title),
                TextColumn::make('video_provider')->label('Video')->badge()->placeholder('—'),
                TextColumn::make('duration_minutes')->label('Minutes')->alignEnd()->placeholder('—'),
                IconColumn::make('is_preview')->label('Preview')->boolean()->trueIcon('heroicon-o-eye')->falseIcon('heroicon-o-lock-closed'),
            ])
            ->headerActions([
                CreateAction::make()->label('Add lesson')->icon('heroicon-m-plus')->slideOver()->modalWidth('4xl')
                    ->mutateDataUsing(fn (array $data) => [...$data, 'sort_order' => (int) $this->getOwnerRecord()->lessons()->max('sort_order') + 1]),
            ])
            ->recordActions([
                Action::make('open')->label('View')->icon('heroicon-o-arrow-top-right-on-square')->iconButton()
                    ->url(fn (Lesson $record) => route('courses.lesson', [$this->getOwnerRecord()->slug, $record->slug]), shouldOpenInNewTab: true),
                EditAction::make()->slideOver()->modalWidth('4xl')->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([DeleteBulkAction::make()])
            ->emptyStateHeading('No lessons yet')
            ->emptyStateDescription('Add the first lesson. Group lessons into sections and drag to reorder.');
    }
}
