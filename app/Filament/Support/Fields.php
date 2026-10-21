<?php

namespace App\Filament\Support;

use App\Enums\AccessLevel;
use App\Enums\ContentStatus;
use App\Filament\Forms\MediaPicker;
use App\Models\User;
use App\Support\RichText;
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
use Illuminate\Support\Str;

/** Reusable dashboard form components shared by all content types. */
class Fields
{
    public static function richEditor(string $name, ?string $label = null): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
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
            ->label('URL')
            ->prefix(rtrim(url($prefix), '/').'/')
            ->required()
            ->maxLength(200)
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->validationMessages(['regex' => 'Use lowercase letters, numbers and hyphens only.'])
            ->unique($table, 'slug', ignoreRecord: true)
            ->helperText('Changing the URL of a published item automatically creates a 301 redirect from the old address.');
    }

    /** Status, schedule, visibility and author — shown in the sidebar of every content editor. */
    public static function publishing(string $permissionPrefix, bool $hasAuthor = true, bool $hasAccess = true, bool $hasFeatured = true): Section
    {
        return Section::make('Publishing')
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
                    ->helperText(fn () => auth()->user()?->can($permissionPrefix.'.publish') ? null : 'An editor will review and publish your work.'),
                DateTimePicker::make('published_at')
                    ->label('Publish date')
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('j M Y, H:i')
                    ->helperText(fn (Get $get) => $get('status') === 'published' ? 'Choose a future date to schedule publication. Leave empty to publish now.' : 'Used when the item is published.'),
                $hasAccess ? Select::make('access_level')
                    ->label('Who can see this')
                    ->options(AccessLevel::options())
                    ->default('public')
                    ->selectablePlaceholder(false)
                    ->helperText('Members-only content shows a teaser and a free sign-up prompt.') : null,
                $hasFeatured ? Toggle::make('is_featured')->label('Feature this item')->helperText('Featured items are highlighted on the homepage and in listings.') : null,
                $hasAuthor ? Select::make('author_id')
                    ->label('Author')
                    ->options(fn () => User::role(User::STAFF_ROLES)->orderBy('name')->pluck('name', 'id'))
                    ->default(fn () => auth()->id())
                    ->searchable()
                    ->disabled(fn () => ! auth()->user()?->can('content.edit_others'))
                    ->dehydrated() : null,
                Placeholder::make('meta')
                    ->hiddenLabel()
                    ->content(fn (?Model $record) => $record?->exists
                        ? new \Illuminate\Support\HtmlString('<div class="text-xs text-gray-500 space-y-1"><div>Created '.e($record->created_at?->diffForHumans()).'</div><div>Last saved '.e($record->updated_at?->diffForHumans()).'</div>'.(isset($record->views_count) ? '<div>'.number_format($record->views_count).' views</div>' : '').'</div>')
                        : null)
                    ->visible(fn (?Model $record) => (bool) $record?->exists),
            ])));
    }

    public static function seo(string $titleField = 'title', string $descriptionField = 'summary'): Section
    {
        return Section::make('Search engine & social sharing')
            ->description('How this page appears on Google, LinkedIn and other networks.')
            ->icon('heroicon-o-magnifying-glass-circle')
            ->collapsible()
            ->collapsed()
            ->schema([
                Group::make()->relationship('seo')->schema([
                    TextInput::make('meta_title')
                        ->label('SEO title')
                        ->maxLength(70)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'Leave empty to use the page title. '.mb_strlen((string) $state).'/60 characters recommended.'),
                    Textarea::make('meta_description')
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(320)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'Leave empty to use the summary. '.mb_strlen((string) $state).'/155 characters recommended.'),
                    TextInput::make('focus_keyword')->label('Focus keyword')->helperText('The main phrase people search for, e.g. “central serous chorioretinopathy OCT”.'),
                    MediaPicker::make('og_image_id')->label('Social sharing image')->helperText('1200×630 px works best. Defaults to the featured image.'),
                    TextInput::make('canonical_url')->label('Canonical URL')->url()->placeholder('Leave empty unless this content is published elsewhere first'),
                    Toggle::make('noindex')->label('Hide from search engines (noindex)'),
                    Toggle::make('nofollow')->label('Ask search engines not to follow links (nofollow)'),
                ]),
                View::make('filament.forms.serp-preview')
                    ->viewData(['titleField' => $titleField, 'descriptionField' => $descriptionField]),
            ]);
    }

    public static function references(string $name = 'references'): Repeater
    {
        return Repeater::make($name)
            ->label('References')
            ->schema([
                Textarea::make('citation')->required()->rows(2)->columnSpanFull()->placeholder('Author A, Author B. Title. Journal. Year;Vol(Issue):pages.'),
                TextInput::make('doi')->label('DOI')->placeholder('10.1016/…'),
                TextInput::make('pmid')->label('PubMed ID'),
                TextInput::make('url')->label('Other link')->url(),
            ])
            ->columns(3)
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state) => isset($state['citation']) ? Str::limit($state['citation'], 80) : null)
            ->defaultItems(0)
            ->addActionLabel('Add reference')
            ->helperText('Cite references in the text as [1], [2] — they become links to this list automatically.');
    }

    public static function topics(): Select
    {
        return Select::make('topics')
            ->label('Topics / diseases')
            ->relationship('topics', 'name')
            ->multiple()
            ->preload()
            ->searchable()
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->parent ? $record->parent->name.' › '.$record->label() : $record->label())
            ->helperText('Links this item to topic pages, filters and related content.');
    }

    public static function tags(): Select
    {
        return Select::make('tags')
            ->relationship('tags', 'name')
            ->multiple()
            ->searchable()
            ->preload()
            ->createOptionForm([TextInput::make('name')->required()->maxLength(80)])
            ->createOptionUsing(fn (array $data) => \App\Models\Tag::create(['name' => $data['name'], 'slug' => Str::slug($data['name'])])->id);
    }

    public static function featuredImage(string $name = 'featured_media_id', string $label = 'Featured image'): MediaPicker
    {
        return MediaPicker::make($name)->label($label);
    }
}
