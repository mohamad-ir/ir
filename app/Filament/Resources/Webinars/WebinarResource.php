<?php

namespace App\Filament\Resources\Webinars;

use App\Enums\WebinarPlatform;
use App\Filament\Forms\MediaPicker;
use App\Filament\Support\ContentTable;
use App\Filament\Support\Fields;
use App\Models\Webinar;
use App\Support\Money;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WebinarResource extends Resource
{
    protected static ?string $model = Webinar::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-video-camera';

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        $tz = config('app.display_timezone', config('app.timezone'));

        return $schema->columns(3)->components([
            Group::make()->columnSpan(['lg' => 2])->schema([
                Section::make()->schema([
                    Fields::title('/webinars'),
                    Fields::slug('/webinars', 'webinars'),
                    Textarea::make('summary')->rows(2)->maxLength(500),
                ]),
                Section::make('Date & online room')->icon('heroicon-o-calendar-days')->schema([
                    Grid::make(3)->schema([
                        DateTimePicker::make('starts_at')->label('Starts')->required()->seconds(false)->native(false)
                            ->timezone($tz)->displayFormat('j M Y, H:i')->helperText('Time zone: '.$tz.' (Settings › Site).')->columnSpan(2),
                        TextInput::make('duration_minutes')->label('Duration (min)')->numeric()->minValue(10)->maxValue(600)->default(60)->required(),
                    ]),
                    ToggleButtons::make('platform')->options(WebinarPlatform::options())->default('zoom')->inline()->required(),
                    TextInput::make('join_url')->label('Join link (Zoom / YouTube Live / Teams)')->url()->maxLength(1000)
                        ->helperText('Kept private and encrypted. Registrants receive a personal link that checks their registration and then forwards to this address, from '.Webinar::JOIN_OPENS_MINUTES.' minutes before the start.'),
                    Textarea::make('join_details')->label('Joining details for registrants')->rows(2)->placeholder('Meeting ID 123 456 789 · Passcode 1234')
                        ->helperText('Included in the one-hour reminder email.'),
                    TextInput::make('recording_url')->label('Recording (YouTube or Vimeo link, after the event)')->url()->maxLength(500)
                        ->helperText('Shown on the webinar page to registrants'.' and, if “free for members”, to members. Use an unlisted/private link.'),
                ]),
                Section::make('Description')->icon('heroicon-o-document-text')->schema([
                    Fields::richEditor('description', 'About this session'),
                ]),
                Fields::seo('title', 'summary'),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Fields::publishing('webinars', hasAccess: false),
                Section::make('Tickets')->icon('heroicon-o-ticket')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('price')->numeric()->minValue(0)->step('0.01')->default(0)->required()->helperText('0 = free (account required).')
                            ->formatStateUsing(fn ($state) => Money::toMajor($state ?? 0))
                            ->dehydrateStateUsing(fn ($state) => Money::toMinor($state)),
                        Select::make('currency')->options(array_combine(array_keys(Money::CURRENCIES), array_keys(Money::CURRENCIES)))
                            ->default(fn () => Settings::get('payments.currency', 'EUR'))->selectablePlaceholder(false)->required(),
                    ]),
                    Toggle::make('free_for_members')->label('Free for members')->default(true),
                    TextInput::make('capacity')->label('Maximum participants')->numeric()->minValue(1)->helperText('Leave empty for no limit.'),
                    Toggle::make('registration_open')->label('Registration open')->default(true),
                ]),
                Section::make('Details')->icon('heroicon-o-tag')->schema([
                    MediaPicker::make('featured_media_id')->label('Cover image'),
                    Fields::topics(),
                    Fields::tags(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('app.display_timezone', config('app.timezone'));

        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('medium')->wrap()
                    ->description(fn (Webinar $record) => $record->platform?->getLabel().' · '.$record->duration_minutes.' min'),
                TextColumn::make('starts_at')->label('Date')->dateTime('D j M Y, H:i', $tz)->sortable()
                    ->description(fn (Webinar $record) => $record->isLive() ? 'Live now' : ($record->hasEnded() ? 'Ended' : $record->starts_at->diffForHumans())),
                TextColumn::make('price')->formatStateUsing(fn (Webinar $record) => $record->priceLabel()),
                TextColumn::make('registrations_count')->label('Registered')->numeric()->sortable()->alignEnd()
                    ->description(fn (Webinar $record) => $record->capacity ? 'of '.$record->capacity : null),
                ContentTable::status(),
            ])
            ->filters([
                ...ContentTable::filters(),
                TernaryFilter::make('upcoming')->label('When')->trueLabel('Upcoming')->falseLabel('Past')
                    ->queries(true: fn (Builder $query) => $query->where('starts_at', '>=', now()), false: fn (Builder $query) => $query->where('starts_at', '<', now())),
            ])
            ->recordActions(ContentTable::recordActions('webinar', 'webinars'))
            ->toolbarActions(ContentTable::bulkActions('webinars'));
    }

    public static function getRelations(): array
    {
        return [RelationManagers\RegistrationsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebinars::route('/'),
            'create' => Pages\CreateWebinar::route('/create'),
            'edit' => Pages\EditWebinar::route('/{record}/edit'),
        ];
    }
}
