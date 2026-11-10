<?php

namespace App\Filament\Support;

use App\Enums\AccessLevel;
use App\Enums\ContentStatus;
use App\Filament\Forms\MediaPicker;
use App\Models\Tag;
use App\Models\User;
use App\Models\Video;
use App\Support\Money;
use App\Support\RichText;
use App\Support\Ui;
use App\Support\VideoEmbed;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Reusable dashboard form components shared by all content types. */
class Fields
{
    public static function richEditor(string $name, ?string $label = null): RichEditor
    {
        return RichEditor::make($name)
            ->label(__($label))
            ->customBlocks(RichText::customBlocks())
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('editor')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->fileAttachmentsMaxSize(20480)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'superscript', 'subscript', 'link', 'highlight'],
                ['h2', 'h3', 'lead', 'small'],
                ['bulletList', 'orderedList', 'blockquote', 'horizontalRule', 'details'],
                ['table', 'attachFiles', 'customBlocks'],
                ['alignStart', 'alignCenter', 'clearFormatting'],
                ['undo', 'redo'],
            ])
            ->extraInputAttributes(['style' => 'min-height: 14rem']);
    }

    /** Optional prices in further currencies, stored as [['currency' => 'GBP', 'amount' => 8500]]. */
    public static function otherPrices(): Repeater
    {
        return Repeater::make('prices')->label(__('Prices in other currencies'))
            ->schema([
                Select::make('currency')->options(array_combine(array_keys(Money::CURRENCIES), array_keys(Money::CURRENCIES)))
                    ->required()->selectablePlaceholder(false)->distinct(),
                TextInput::make('amount')->label(__('Price'))->numeric()->minValue(0.5)->step('0.01')->required()
                    ->formatStateUsing(fn ($state) => Money::toMajor($state === null ? null : (int) $state))
                    ->dehydrateStateUsing(fn ($state) => Money::toMinor($state)),
            ])
            ->columns(2)->defaultItems(0)->addActionLabel(__('Add a price in another currency'))
            ->helperText(__('Visitors see and pay the price in their currency (chosen by them, or detected from their country). Without one, they pay the main price.'));
    }

    /** A list of videos (YouTube/Vimeo link, uploaded file or library video) with title and caption. */
    public static function videoList(string $name = 'videos'): Repeater
    {
        return Repeater::make($name)->label(__('Videos'))
            ->schema([
                ToggleButtons::make('source')->label(__('Video from'))->options(VideoEmbed::SOURCES)->default('url')->inline()->live()->columnSpanFull(),
                TextInput::make('url')->label(__('YouTube or Vimeo link'))->url()->columnSpanFull()
                    ->visible(fn ($get) => ($get('source') ?? 'url') === 'url')->required(fn ($get) => ($get('source') ?? 'url') === 'url'),
                MediaPicker::make('media_id')->label(__('Video file'))->videosOnly()->columnSpanFull()
                    ->visible(fn ($get) => $get('source') === 'upload')->required(fn ($get) => $get('source') === 'upload'),
                Select::make('video_id')->label(__('Video'))->searchable()->columnSpanFull()
                    ->options(fn () => Video::latest()->limit(50)->pluck('title', 'id'))
                    ->getSearchResultsUsing(fn (string $search) => Video::where('title', 'like', "%{$search}%")->limit(30)->pluck('title', 'id'))
                    ->visible(fn ($get) => $get('source') === 'library')->required(fn ($get) => $get('source') === 'library'),
                TextInput::make('title')->maxLength(160),
                TextInput::make('caption')->maxLength(300),
            ])
            ->columns(2)->defaultItems(0)->reorderable()->collapsible()
            ->itemLabel(fn (array $state) => $state['title'] ?? null)
            ->addActionLabel(__('Add video'));
    }

    public static function title(string $prefix): TextInput
    {
        return TextInput::make('title')
            ->required()
            ->maxLength(250)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?Model $record) {
                if (! $record?->exists || blank($get('slug'))) {
                    $set('slug', Str::slug((string) $state));
                }
            })
            ->extraInputAttributes(['class' => 'text-lg font-medium']);
    }

    public static function slug(string $prefix, string $table): TextInput
    {
        return TextInput::make('slug')
            ->label(__('URL'))
            ->prefix(rtrim(url($prefix), '/').'/')
            ->required()
            ->maxLength(200)
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->validationMessages(Ui::options(['regex' => 'Use lowercase letters, numbers and hyphens only.']))
            ->unique($table, 'slug', ignoreRecord: true)
            ->helperText(__('Changing the URL of a published item automatically creates a 301 redirect from the old address.'));
    }

    /** Status, schedule, visibility and author — shown in the sidebar of every content editor. */
    public static function publishing(string $permissionPrefix, bool $hasAuthor = true, bool $hasAccess = true, bool $hasFeatured = true, bool $hasComments = false): Section
    {
        return Section::make('Publishing')->heading(__('Publishing'))
            ->icon('heroicon-o-paper-airplane')
            ->schema(array_values(array_filter([
                ToggleButtons::make('status')
                    ->options(function (?Model $record) use ($permissionPrefix) {
                        $canPublish = auth()->user()?->can($permissionPrefix.'.publish');
                        $options = ContentStatus::options();
                        if (! $canPublish && $record?->status !== ContentStatus::Published) {
                            unset($options['published']);
                        }

                        return $options;
                    })
                    ->colors(collect(ContentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getColor()])->all())
                    ->icons(collect(ContentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getIcon()])->all())
                    ->default('draft')
                    ->required()
                    ->live()
                    ->columns(2)
                    ->helperText(fn () => auth()->user()?->can($permissionPrefix.'.publish') ? null : __('An editor will review and publish your work.')),
                DateTimePicker::make('published_at')
                    ->label(__('Publish date'))
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('j M Y, H:i')
                    ->helperText(fn (Get $get) => $get('status') === 'published' ? __('Choose a future date to schedule publication. Leave empty to publish now.') : __('Used when the item is published.')),
                $hasAccess ? Select::make('access_level')
                    ->label(__('Who can see this'))
                    ->options(AccessLevel::options())
                    ->default('public')
                    ->selectablePlaceholder(false)
                    ->helperText(__('Members-only content shows a teaser and a free sign-up prompt.')) : null,
                $hasFeatured ? Toggle::make('is_featured')->label(__('Feature this item'))->helperText(__('Featured items are highlighted on the homepage and in listings.')) : null,
                $hasComments ? Toggle::make('comments_enabled')->label(__('Allow comments'))->default(true)
                    ->helperText(fn (?Model $record) => ($record?->comments_count ? __(':count published comments.', ['count' => number_format($record->comments_count)]).' ' : '').__('Signed-in members can discuss this item. Turn off to close the discussion (existing comments stay visible).')) : null,
                $hasAuthor ? Select::make('author_id')
                    ->label(__('Author'))
                    ->options(fn () => User::dashboardUsers()->orderBy('name')->pluck('name', 'id'))
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->disabled(fn () => ! auth()->user()?->can('content.edit_others'))
                    ->dehydrated() : null,
                Placeholder::make('meta')
                    ->hiddenLabel()
                    ->content(fn (?Model $record) => $record?->exists
                        ? new HtmlString('<div class="text-xs text-gray-500 space-y-1"><div>'.e(__('Created :ago', ['ago' => $record->created_at?->diffForHumans()])).'</div><div>'.e(__('Last saved :ago', ['ago' => $record->updated_at?->diffForHumans()])).'</div>'.(isset($record->views_count) ? '<div>'.e(__(':count views', ['count' => number_format($record->views_count)])).'</div>' : '').'</div>')
                        : null)
                    ->visible(fn (?Model $record) => (bool) $record?->exists),
            ])));
    }

    public static function seo(string $titleField = 'title', string $descriptionField = 'summary'): Section
    {
        return Section::make('Search engine & social sharing')->heading(__('Search engine & social sharing'))
            ->description(__('How this page appears on Google, LinkedIn and other networks.'))
            ->icon('heroicon-o-magnifying-glass-circle')
            ->collapsible()
            ->collapsed()
            ->schema([
                Group::make()->relationship('seo')->schema([
                    TextInput::make('meta_title')
                        ->label(__('SEO title'))
                        ->maxLength(70)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => __('Leave empty to use the page title.').' '.__(':count/:max characters recommended.', ['count' => mb_strlen((string) $state), 'max' => 60])),
                    Textarea::make('meta_description')
                        ->label(__('Meta description'))
                        ->rows(3)
                        ->maxLength(320)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => __('Leave empty to use the summary.').' '.__(':count/:max characters recommended.', ['count' => mb_strlen((string) $state), 'max' => 155])),
                    TextInput::make('focus_keyword')->label(__('Focus keyword'))->helperText(__('The main phrase people search for, e.g. “central serous chorioretinopathy OCT”.')),
                    MediaPicker::make('og_image_id')->label(__('Social sharing image'))->helperText(__('1200×630 px works best. Defaults to the featured image.')),
                    TextInput::make('canonical_url')->label(__('Canonical URL'))->url()->placeholder(__('Leave empty unless this content is published elsewhere first')),
                    Toggle::make('noindex')->label(__('Hide from search engines (noindex)')),
                    Toggle::make('nofollow')->label(__('Ask search engines not to follow links (nofollow)')),
                ]),
                View::make('filament.forms.serp-preview')
                    ->viewData(['titleField' => $titleField, 'descriptionField' => $descriptionField]),
            ]);
    }

    public static function references(string $name = 'references'): Repeater
    {
        return Repeater::make($name)
            ->label(__('References'))
            ->schema([
                Textarea::make('citation')->required()->rows(2)->columnSpanFull()->placeholder(__('Author A, Author B. Title. Journal. Year;Vol(Issue):pages.')),
                TextInput::make('doi')->label(__('DOI'))->placeholder('10.1016/…'),
                TextInput::make('pmid')->label(__('PubMed ID')),
                TextInput::make('url')->label(__('Other link'))->url(),
            ])
            ->columns(3)
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state) => isset($state['citation']) ? Str::limit($state['citation'], 80) : null)
            ->defaultItems(0)
            ->addActionLabel(__('Add reference'))
            ->helperText(__('Cite references in the text as [1], [2] — they become links to this list automatically.'));
    }

    public static function topics(): Select
    {
        return Select::make('topics')
            ->label(__('Topics / diseases'))
            ->relationship('topics', 'name')
            ->multiple()
            ->preload()
            ->searchable()
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->parent ? $record->parent->name.' › '.$record->label() : $record->label())
            ->helperText(__('Links this item to topic pages, filters and related content.'));
    }

    public static function tags(): Select
    {
        return Select::make('tags')
            ->relationship('tags', 'name')
            ->multiple()
            ->searchable()
            ->preload()
            ->createOptionForm([TextInput::make('name')->required()->maxLength(80)])
            ->createOptionUsing(fn (array $data) => Tag::create(['name' => $data['name'], 'slug' => Str::slug($data['name'])])->id);
    }

    public static function featuredImage(string $name = 'featured_media_id', string $label = 'Featured image'): MediaPicker
    {
        return MediaPicker::make($name)->label(__($label));
    }
}
