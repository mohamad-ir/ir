<?php

namespace App\Filament\Resources\Videos;

use App\Enums\Difficulty;
use App\Enums\VideoProvider;
use App\Filament\Forms\MediaPicker;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\Category;
use App\Models\Video;
use App\Services\Media\VideoMetadata;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::title('/videos'),
                    Fields::slug('/videos', 'videos'),
                    Textarea::make('summary')->rows(2)->maxLength(400),
                ]),
                Section::make('Video source')->icon('heroicon-o-film')->schema([
                    ToggleButtons::make('provider')->options(VideoProvider::options())->default('youtube')->inline()->live()->required(),
                    TextInput::make('video_url')->label(fn (Get $get) => $get('provider') === 'url' ? 'Video file URL (MP4)' : 'Video link')
                        ->placeholder(fn (Get $get) => $get('provider') === 'vimeo' ? 'https://vimeo.com/123456789' : 'https://www.youtube.com/watch?v=…')
                        ->visible(fn (Get $get) => in_array($get('provider'), ['youtube', 'vimeo', 'url']))
                        ->required(fn (Get $get) => in_array($get('provider'), ['youtube', 'vimeo', 'url']))
                        ->url()->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                            if (! $state || ! in_array($get('provider'), ['youtube', 'vimeo'])) {
                                return;
                            }
                            $meta = app(VideoMetadata::class)->fetch(VideoProvider::from($get('provider')), $state);
                            if ($meta['title'] ?? null) {
                                if (blank($get('title'))) {
                                    $set('title', $meta['title']);
                                    $set('slug', str($meta['title'])->slug()->toString());
                                }
                            }
                            if (($meta['duration'] ?? null) && blank($get('duration_seconds'))) {
                                $set('duration_seconds', $meta['duration']);
                            }
                            if ($meta['thumbnail'] ?? null) {
                                $set('thumbnail_url', $meta['thumbnail']);
                            }
                        })
                        ->helperText('Paste a YouTube or Vimeo link — title, thumbnail and duration are filled in automatically where available. Videos play in privacy-enhanced mode.'),
                    MediaPicker::make('video_media_id')->label('Video file')->videosOnly()
                        ->visible(fn (Get $get) => $get('provider') === 'file')
                        ->required(fn (Get $get) => $get('provider') === 'file')
                        ->helperText('Shared hosting is not designed for streaming: keep uploads short (under ~100 MB) and use YouTube or Vimeo for long recordings.'),
                    Grid::make(2)->schema([
                        TextInput::make('duration_seconds')->label('Duration (seconds)')->numeric()->minValue(0),
                        TextInput::make('thumbnail_url')->label('Thumbnail URL')->url()->helperText('Filled automatically for YouTube/Vimeo.'),
                    ]),
                    MediaPicker::make('thumbnail_media_id')->label('Custom thumbnail')->helperText('Overrides the provider thumbnail. Members-only YouTube videos need one: the automatic YouTube thumbnail would reveal the video link.'),
                ]),
                Section::make('Description, chapters & transcript')->icon('heroicon-o-document-text')->schema([
                    Fields::richEditor('description', 'Description / notes'),
                    Repeater::make('chapters')->schema([
                        TextInput::make('time')->placeholder('mm:ss')->regex('/^\d{1,2}(:\d{2}){1,2}$/')->required(),
                        TextInput::make('title')->required()->columnSpan(3),
                    ])->columns(4)->reorderable()->defaultItems(0)->addActionLabel('Add chapter')->helperText('Chapters appear beside the player and are sent to Google as key moments.'),
                    Textarea::make('transcript')->rows(8)->helperText('Improves accessibility and makes the video searchable.'),
                ]),
                Fields::seo('title', 'summary'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('videos'),
                Section::make('Classification')->icon('heroicon-o-tag')->schema([
                    Select::make('category_id')->label('Category')->relationship('category', 'name', fn (Builder $query) => $query->where('type', 'video'))
                        ->createOptionForm([TextInput::make('name')->required()])
                        ->createOptionUsing(fn (array $data) => Category::create(['type' => 'video', 'name' => $data['name'], 'slug' => str($data['name'])->slug()])->id)
                        ->preload()->searchable(),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['thumbnailMedia', 'category', 'author']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('thumb')->label('')->getStateUsing(fn (Video $record) => $record->thumbnail())->imageWidth(80)->imageHeight(45)->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('title')->searchable()->sortable()->weight('medium')->wrap()->extraHeaderAttributes(['style' => 'min-width: 20rem'])->description(fn (Video $record) => trim(($record->category?->name ?? '').' · '.($record->durationLabel() ?? ''), ' ·')),
                TextColumn::make('provider')->badge()->toggleable(),
                ContentTable::status(),
                ContentTable::published(),
                TextColumn::make('plays_count')->label('Plays')->numeric()->sortable()->alignEnd(),
                TextColumn::make('completion')->label('Completion')->getStateUsing(fn (Video $record) => $record->plays_count ? round($record->completions_count / $record->plays_count * 100).'%' : '—')->alignEnd(),
                ContentTable::views(),
            ])
            ->filters([...ContentTable::filters(), SelectFilter::make('category_id')->label('Category')->relationship('category', 'name', fn (Builder $query) => $query->where('type', 'video'))])
            ->recordActions(ContentTable::recordActions('video', 'videos'))
            ->toolbarActions(ContentTable::bulkActions('videos'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
