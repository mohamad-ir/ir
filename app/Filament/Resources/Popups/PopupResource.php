<?php

namespace App\Filament\Resources\Popups;

use App\Filament\Concerns\TranslatesResourceLabels;
use App\Filament\Forms\MediaPicker;
use App\Models\Popup;
use App\Services\Popups\PopupService;
use App\Services\Popups\PopupTemplates;
use App\Support\Settings;
use App\Support\Ui;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PopupResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = Popup::class;

    protected static ?string $modelLabel = 'popup';

    protected static ?string $pluralModelLabel = 'popups & announcements';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Design';

    protected static ?string $navigationLabel = 'Popups & announcements';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $live = app(PopupService::class)->published()->filter(fn (Popup $p) => $p->isLive())->count();

        return $live ? (string) $live : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Showing on the website now');
    }

    public static function form(Schema $schema): Schema
    {
        $type = fn ($get) => $get('type') ?: 'modal';
        $notBar = fn ($get) => $type($get) !== 'bar';
        $link = 'regex:~^(/|#|https?://|mailto:|tel:)~i';

        return $schema->columns(1)->components([
            Grid::make(['default' => 1, 'xl' => 12])->schema([
                Group::make()->columnSpan(['xl' => 7])->schema([
                    Section::make()->compact()->visibleOn('create')->schema([
                        Select::make('template')->label(__('Start from a template'))
                            ->options(Ui::options(PopupTemplates::options()))->placeholder(__('Blank'))
                            ->helperText(fn ($state) => $state ? __(PopupTemplates::all()[$state]['description'] ?? '') : __('Choose a template to fill in the content, design and rules. You can change everything afterwards.'))
                            ->live()->afterStateUpdated(function ($state, $set) {
                                if (! $state) {
                                    return;
                                }
                                foreach (PopupTemplates::values($state) as $key => $value) {
                                    $set($key, $value);
                                }
                                $set('name', __(PopupTemplates::all()[$state]['label']));
                            }),
                    ]),
                    Tabs::make()->persistTabInQueryString('tab')->tabs([
                        Tab::make('Content')->label(__('Content'))->icon('heroicon-o-pencil-square')->schema([
                            TextInput::make('name')->label(__('Name (only shown in the dashboard)'))->required()->maxLength(120)->placeholder(__('Spring course launch')),
                            ToggleButtons::make('type')->label(__('Kind'))->options(Ui::options(Popup::TYPES))->default('modal')->inline()->required()->live()
                                ->icons(['modal' => 'heroicon-o-window', 'slide_in' => 'heroicon-o-chat-bubble-bottom-center-text', 'bar' => 'heroicon-o-minus'])
                                ->afterStateUpdated(fn ($state, $set) => $set('position', array_key_first(Popup::POSITIONS[$state] ?? Popup::POSITIONS['modal'])))
                                ->helperText(__('Popup: in the middle of the screen. Slide-in: small box in a corner that does not block reading. Announcement bar: one line at the top or bottom.')),
                            Grid::make(2)->schema([
                                TextInput::make('eyebrow')->label(__('Small label above the title'))->maxLength(80)->placeholder(__('New course'))->live(debounce: 600),
                                TextInput::make('title')->label(fn ($get) => $type($get) === 'bar' ? __('Message') : __('Title'))->maxLength(160)->required(fn ($get) => $type($get) === 'bar')->live(debounce: 600),
                            ]),
                            RichEditor::make('body')->label(__('Text'))->visible($notBar)
                                ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList']]),
                            MediaPicker::make('media_id')->label(__('Image (optional)'))->visible($notBar)->live()
                                ->helperText(__('Landscape image, about 1200 × 675 px. Choose how it is placed in the Design tab.')),
                            ToggleButtons::make('form')->label(__('Form'))->options(Ui::options(Popup::FORMS))->default('none')->inline()->visible($notBar)->live()
                                ->helperText(__('The newsletter form adds subscribers to Audience › Subscribers (with confirmation email when double opt-in is on). Sign-ups are counted as conversions.')),
                            Section::make('Buttons')->heading(__('Buttons'))->compact()->columns(2)->schema([
                                TextInput::make('cta_label')->label(fn ($get) => $type($get) === 'bar' ? __('Link text') : __('Button text'))->maxLength(60)->placeholder(__('See the course'))->live(debounce: 600)
                                    ->requiredWith('cta_url'),
                                TextInput::make('cta_url')->label(__('Button link'))->maxLength(500)->placeholder(__('/courses/oct-masterclass or https://…'))
                                    ->rule($link)->validationMessages(Ui::options(['regex' => 'Start with / for a page on this site, # for a place on the page, or https:// for another website.']))
                                    ->requiredWith('cta_label'),
                                Toggle::make('cta_new_tab')->label(__('Open the link in a new tab')),
                                TextInput::make('dismiss_label')->label(__('“No thanks” button (optional)'))->maxLength(60)->placeholder(__('No thanks'))->visible($notBar)->live(debounce: 600),
                            ]),
                        ]),

                        Tab::make('Design')->label(__('Design'))->icon('heroicon-o-paint-brush')->schema([
                            ToggleButtons::make('position')->label(__('Position'))->inline()->required()->live()
                                ->options(fn ($get) => Ui::options(Popup::POSITIONS[$type($get)] ?? Popup::POSITIONS['modal']))
                                ->default('center'),
                            ToggleButtons::make('size')->label(__('Size'))->options(Ui::options(Popup::SIZES))->default('md')->inline()->visible($notBar)->live(),
                            ToggleButtons::make('color_scheme')->label(__('Colours'))->options(Ui::options(Popup::SCHEMES))->default('default')->inline()->live()
                                ->helperText(__('Theme colours follow Light/Dark mode automatically. Custom colours stay the same in both modes.')),
                            Grid::make(3)->visible(fn ($get) => $get('color_scheme') === 'custom')->schema([
                                ColorPicker::make('bg_color')->label(__('Background'))->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600),
                                ColorPicker::make('text_color')->label(__('Text'))->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600)->helperText(__('Empty = automatic contrast')),
                                ColorPicker::make('button_color')->label(__('Button'))->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600),
                            ]),
                            ToggleButtons::make('image_style')->label(__('Image placement'))->options(Ui::options(Popup::IMAGE_STYLES))->default('top')->inline()
                                ->visible(fn ($get) => $notBar($get) && filled($get('media_id')))->live(),
                            ToggleButtons::make('animation')->label(__('Animation'))->options(Ui::options(Popup::ANIMATIONS))->default('fade')->inline()->live()
                                ->helperText(__('Visitors who ask their device for reduced motion never see animations.')),
                            Grid::make(2)->schema([
                                Toggle::make('show_overlay')->label(__('Darken the page behind the popup'))->default(true)->visible(fn ($get) => $type($get) === 'modal')->live(),
                                Toggle::make('close_on_overlay')->label(__('Close when clicking outside'))->default(true)->visible(fn ($get) => $type($get) === 'modal' && $get('show_overlay')),
                                Toggle::make('show_close')->label(__('Show the close (×) button'))->default(true)->live()
                                    ->helperText(__('Recommended. Esc always closes popups.')),
                                TextInput::make('auto_close')->label(__('Close automatically after (seconds)'))->numeric()->minValue(3)->maxValue(600)->placeholder(__('Never'))
                                    ->visible(fn ($get) => $type($get) !== 'modal')->helperText(__('Paused while the pointer is over it.')),
                            ]),
                        ]),

                        Tab::make('Display rules')->label(__('Display rules'))->icon('heroicon-o-adjustments-horizontal')->schema([
                            Section::make('When')->heading(__('When'))->compact()->columns(2)->schema([
                                Select::make('trigger')->label(__('Show'))->options(Ui::options(Popup::TRIGGERS))->default('delay')->required()->live()->native(false),
                                TextInput::make('trigger_value')->numeric()->minValue(1)
                                    ->label(fn ($get) => match ($get('trigger')) {
                                        'delay' => __('Seconds after the page opens'),
                                        'scroll' => __('Percentage of the page scrolled'),
                                        'idle' => __('Seconds without activity'),
                                        'pageviews' => __('From page number (in one visit)'),
                                        default => __('Value'),
                                    })
                                    ->maxValue(fn ($get) => $get('trigger') === 'scroll' ? 100 : 3600)
                                    ->placeholder(fn ($get) => ['delay' => '5', 'scroll' => '50', 'idle' => '30', 'pageviews' => '2'][$get('trigger')] ?? null)
                                    ->visible(fn ($get) => in_array($get('trigger'), ['delay', 'scroll', 'idle', 'pageviews'], true)),
                                Placeholder::make('click_help')->hiddenLabel()->columnSpanFull()
                                    ->visible(fn ($get) => $get('trigger') === 'click')
                                    ->content(fn ($record) => new HtmlString(e(__('Add a link to any page or menu with this address:')).' <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/10">#popup-'.($record?->id ?? 'ID').'</code>. '.e(__('Popups opened this way ignore the frequency limits.')))),
                                Placeholder::make('exit_help')->hiddenLabel()->columnSpanFull()
                                    ->visible(fn ($get) => $get('trigger') === 'exit')
                                    ->content(__('On computers: when the pointer moves towards the browser tabs. On phones and tablets: when the visitor quickly scrolls back up after reading.')),
                            ]),
                            Section::make('Where')->heading(__('Where'))->compact()->schema([
                                TagsInput::make('include_paths')->label(__('Only on these pages'))->placeholder(__('Add a page address and press Enter'))
                                    ->helperText(__('Empty = every page. Use * as a wildcard: /courses/* = all course pages, /cases/* = all cases, / = homepage only.'))
                                    ->nestedRecursiveRules(['max:300']),
                                TagsInput::make('exclude_paths')->label(__('Never on these pages'))->placeholder(__('Add a page address and press Enter'))
                                    ->nestedRecursiveRules(['max:300']),
                                Placeholder::make('system_paths')->hiddenLabel()
                                    ->content(__('Popups never appear in the dashboard or on sign-in, registration, password and checkout pages.')),
                            ]),
                            Section::make('Who')->heading(__('Who'))->compact()->columns(2)->schema([
                                Select::make('audience')->label(__('Audience'))->options(Ui::options(Popup::AUDIENCES))->default('everyone')->required()->native(false),
                                Select::make('visitors')->label(__('Visit'))->options(Ui::options(Popup::VISITORS))->default('all')->required()->native(false),
                                CheckboxList::make('devices')->label(__('Devices'))->options(Ui::options(Popup::DEVICES))->columns(3)->helperText(__('None ticked = all devices.')),
                                TextInput::make('source_match')->label(__('Visitors coming from (optional)'))->maxLength(120)->placeholder(__('e.g. facebook, google or spring-sale'))
                                    ->helperText(__('Matches the referring website or the utm_source / utm_medium / utm_campaign of the link that started the visit.')),
                            ]),
                        ]),

                        Tab::make('Schedule & limits')->label(__('Schedule & limits'))->icon('heroicon-o-calendar-days')->schema([
                            Section::make('Publishing')->heading(__('Publishing'))->compact()->columns(2)->schema([
                                ToggleButtons::make('status')->label(__('Status'))->options(Ui::options(Popup::STATUSES))->default('draft')->inline()->required()
                                    ->colors(['draft' => 'gray', 'published' => 'success', 'paused' => 'warning']),
                                TextInput::make('priority')->label(__('Priority'))->numeric()->default(0)->minValue(-100)->maxValue(100)
                                    ->helperText(__('When several popups could appear in the same place, only the highest priority is shown.')),
                                DateTimePicker::make('starts_at')->label(__('Start'))->seconds(false)->native(false)->placeholder(__('Immediately'))
                                    ->timezone(config('app.display_timezone', config('app.timezone'))),
                                DateTimePicker::make('ends_at')->label(__('End'))->seconds(false)->native(false)->after('starts_at')->placeholder(__('No end date'))
                                    ->timezone(config('app.display_timezone', config('app.timezone'))),
                                CheckboxList::make('days')->label(__('Days of the week'))->options(Ui::options(Popup::WEEKDAYS))->columns(7)->columnSpanFull()
                                    ->helperText(__('None ticked = every day.')),
                                TimePicker::make('time_from')->label(__('Daily from'))->seconds(false)->native(false)->format('H:i'),
                                TimePicker::make('time_to')->label(__('Daily until'))->seconds(false)->native(false)->format('H:i')
                                    ->helperText(fn () => __('Time zone: :tz (Settings › Site).', ['tz' => config('app.display_timezone', config('app.timezone'))])),
                            ]),
                            Section::make('How often each visitor sees it')->heading(__('How often each visitor sees it'))->compact()->columns(2)->schema([
                                Select::make('frequency')->label(__('Frequency'))->options(Ui::options(Popup::FREQUENCIES))->default('days')->required()->live()->native(false),
                                TextInput::make('frequency_days')->label(__('At most once every (days)'))->numeric()->minValue(1)->maxValue(365)->default(7)
                                    ->visible(fn ($get) => $get('frequency') === 'days'),
                                TextInput::make('after_close_days')->label(__('After closing, hide for (days)'))->numeric()->minValue(0)->maxValue(365)->placeholder('0')
                                    ->helperText(__('0 = follow the frequency only.')),
                                TextInput::make('max_views')->label(__('Maximum views per visitor'))->numeric()->minValue(1)->maxValue(1000)->placeholder(__('No limit')),
                                Toggle::make('hide_after_conversion')->label(__('Stop showing after the visitor clicks the button or signs up'))->default(true)->columnSpanFull(),
                            ]),
                            Section::make('Campaign limit')->heading(__('Campaign limit'))->compact()->schema([
                                TextInput::make('max_impressions')->label(__('Stop after this many views in total'))->numeric()->minValue(1)->placeholder(__('No limit'))
                                    ->helperText(__('Useful for limited offers, e.g. the first 500 visitors.')),
                            ]),
                            Placeholder::make('global_limits')->hiddenLabel()
                                ->content(fn () => __('Site-wide limits (Popup settings on the list page): at most :n automatic popups per visit, with at least :m minutes between them. Announcement bars and popups opened by a link are not limited.', [
                                    'n' => (int) Settings::get('popups.max_per_session', 2), 'm' => (int) Settings::get('popups.cooldown_minutes', 10),
                                ])),
                        ]),
                    ]),
                ]),
                Group::make()->columnSpan(['xl' => 5])->extraAttributes(['class' => 'xl:sticky xl:top-20 xl:self-start'])->schema([
                    Section::make('Live preview')->heading(__('Live preview'))->compact()->schema([
                        ViewField::make('preview')->hiddenLabel()->dehydrated(false)->view('filament.popup-preview'),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $service = fn () => app(PopupService::class);

        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->weight('medium')->searchable(['name', 'title'])
                    ->description(fn (Popup $record) => __(Popup::TYPES[$record->type] ?? $record->type).' · '.Str::limit((string) $record->title, 60)),
                TextColumn::make('state')->label(__('Status'))->badge()
                    ->state(fn (Popup $record) => __($record->stateLabel()))
                    ->color(fn (Popup $record) => $record->stateColor())
                    ->description(fn (Popup $record) => match (true) {
                        $record->ends_at && $record->ends_at->isFuture() && $record->status === 'published' => __('Until :date', ['date' => $record->ends_at->timezone(config('app.display_timezone', config('app.timezone')))->format('j M, H:i')]),
                        $record->starts_at && $record->starts_at->isFuture() => __('From :date', ['date' => $record->starts_at->timezone(config('app.display_timezone', config('app.timezone')))->format('j M, H:i')]),
                        default => null,
                    }),
                TextColumn::make('trigger')->label(__('Shows'))->formatStateUsing(fn ($state) => __(Popup::TRIGGERS[$state] ?? $state))->toggleable()->wrap(),
                TextColumn::make('priority')->label(__('Priority'))->numeric()->alignEnd()->sortable()->toggleable(),
                TextColumn::make('views_count')->label(__('Views'))->numeric()->alignEnd()->sortable(),
                TextColumn::make('clicks_count')->label(__('Clicks'))->numeric()->alignEnd()->sortable()
                    ->description(fn (Popup $record) => $record->ctr() !== null ? __(':rate% CTR', ['rate' => $record->ctr()]) : null),
                TextColumn::make('conversions_count')->label(__('Sign-ups'))->numeric()->alignEnd()->sortable()->toggleable(),
                TextColumn::make('closes_count')->label(__('Closed'))->numeric()->alignEnd()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label(__('Updated'))->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Status'))->options(Ui::options(Popup::STATUSES)),
                SelectFilter::make('type')->label(__('Kind'))->options(Ui::options(Popup::TYPES)),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                ActionGroup::make([
                    Action::make('publish')->label(__('Publish'))->icon('heroicon-o-play')->color('success')
                        ->visible(fn (Popup $record) => $record->status !== 'published')
                        ->action(fn (Popup $record) => $record->update(['status' => 'published'])),
                    Action::make('pause')->label(__('Pause'))->icon('heroicon-o-pause')->color('warning')
                        ->visible(fn (Popup $record) => $record->status === 'published')
                        ->action(fn (Popup $record) => $record->update(['status' => 'paused'])),
                    Action::make('preview')->label(__('Preview on the website'))->icon('heroicon-o-eye')
                        ->url(fn (Popup $record) => route('preview.popup', $record), shouldOpenInNewTab: true),
                    ReplicateAction::make()->label(__('Duplicate as draft'))
                        ->excludeAttributes(['views_count', 'clicks_count', 'closes_count', 'conversions_count', 'stats_reset_at', 'revision', 'created_by'])
                        ->mutateRecordDataUsing(fn (array $data) => [...$data, 'name' => $data['name'].' '.__('(copy)'), 'status' => 'draft']),
                    Action::make('show_again')->label(__('Show again to everyone'))->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()->modalDescription(__('Visitors who already saw, closed or used this popup will see it again (frequency limits start over). Statistics are kept.'))
                        ->action(function (Popup $record) use ($service) {
                            $service()->showAgain($record);
                            Notification::make()->success()->title(__('Visitors will see it again'))->send();
                        }),
                    Action::make('reset_stats')->label(__('Reset statistics'))->icon('heroicon-o-chart-bar')->color('danger')
                        ->requiresConfirmation()->modalDescription(__('Views, clicks, closes and sign-ups are set to zero. This cannot be undone.'))
                        ->action(function (Popup $record) use ($service) {
                            $service()->resetStats($record);
                            Notification::make()->success()->title(__('Statistics reset'))->send();
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label(__('Publish'))->icon('heroicon-o-play')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'published']))->deselectRecordsAfterCompletion(),
                    BulkAction::make('pause')->label(__('Pause'))->icon('heroicon-o-pause')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'paused']))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-megaphone')
            ->emptyStateHeading(__('No popups yet'))
            ->emptyStateDescription(__('Create a popup, slide-in or announcement bar — start from a template, choose where and when it appears, and follow its results here.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPopups::route('/'),
            'create' => Pages\CreatePopup::route('/create'),
            'edit' => Pages\EditPopup::route('/{record}/edit'),
        ];
    }
}
