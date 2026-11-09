<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Forms\MediaPicker;
use App\Models\Article;
use App\Models\ClinicalCase;
use App\Services\Theme\ThemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

class ThemeSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/theme';

    protected static ?string $navigationLabel = 'Theme & branding';

    protected static ?string $title = 'Theme & branding';

    protected static string|\UnitEnum|null $navigationGroup = 'Design';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 4;

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $previewToken = '';

    public string $previewPath = '/';

    protected function groups(): array
    {
        return ['theme', 'branding', 'site', 'pwa'];
    }

    /** Only the identity fields of the "site" group are edited here; the rest stays in General settings. */
    protected function mutateStateBeforeSave(array $state): array
    {
        $state['site'] = \Illuminate\Support\Arr::only($state['site'] ?? [], ['name', 'tagline']);
        unset($state['branding']['icon_set']);

        if ($errors = app(\App\Services\Branding\BrandingService::class)->validate($state['branding'] ?? [])) {
            throw \Illuminate\Validation\ValidationException::withMessages(collect($errors)->mapWithKeys(fn ($m, $k) => ['data.'.$k => $m])->all());
        }

        return $state;
    }

    protected function permission(): string
    {
        return 'design.manage';
    }

    public function mount(): void
    {
        $this->previewToken = Str::random(40);
        parent::mount();
    }

    #[Renderless]
    public function refreshPreview(): void
    {
        Cache::put('theme-preview:'.$this->previewToken, $this->data['theme'] ?? [], now()->addHours(2));
        $this->dispatch('theme-preview-updated');
    }

    public function previewUrl(): string
    {
        return url($this->previewPath).'?theme_preview='.$this->previewToken;
    }

    public function previewPaths(): array
    {
        return array_filter([
            '/' => 'Homepage',
            '/cases' => 'Case library',
            ClinicalCase::published()->latest('published_at')->first()?->urlPath() => 'Case page',
            Article::published()->latest('published_at')->first()?->urlPath() => 'Article',
            '/exam-prep' => 'Exam hub',
        ], fn ($v, $k) => filled($k), ARRAY_FILTER_USE_BOTH);
    }

    protected function afterSave(): void
    {
        Cache::forget('theme.css');
        app(\App\Services\Branding\BrandingService::class)->syncIcons();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preset')->label(__('Apply a colour preset'))->icon('heroicon-o-sparkles')->color('gray')
                ->schema([ToggleButtons::make('preset')->options(collect(ThemeService::PRESETS)->map(fn ($p) => $p['label']))->required()->columns(1)])
                ->action(function (array $data) {
                    $preset = ThemeService::PRESETS[$data['preset']];
                    foreach (['primary', 'accent', 'highlight', 'background', 'surface', 'text'] as $key) {
                        $this->data['theme'][$key] = $preset[$key];
                    }
                    $this->refreshPreview();
                }),
        ];
    }

    protected function components(): array
    {
        $color = fn (string $key, string $label, string $help) => ColorPicker::make("theme.{$key}")->label($label)->helperText($help)->required()->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600);

        return [
            Grid::make(['default' => 1, 'xl' => 12])->schema([
                Group::make()->columnSpan(['xl' => 5])->schema([
                    Tabs::make()->persistTabInQueryString()->tabs([
                        Tab::make('Colours')->label(__('Colours'))->icon('heroicon-o-swatch')->schema([
                            Grid::make(2)->schema([
                                $color('primary', 'Primary', 'Headings, buttons, dark sections'),
                                $color('accent', 'Accent', 'Links, highlights, active states'),
                                $color('highlight', 'Highlight', 'Badges such as “Case of the week”'),
                                $color('text', 'Body text', 'Main reading colour'),
                                $color('background', 'Page background', 'Behind all content'),
                                $color('surface', 'Cards', 'Cards, panels and inputs'),
                            ]),
                            Placeholder::make('contrast')->hiddenLabel()->content(function ($get) {
                                $warnings = app(ThemeService::class)->audit($get('theme') ?? []);

                                return new HtmlString($warnings
                                    ? '<div class="rounded-lg bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-500/10 dark:text-warning-300">'.implode('<br>', array_map('e', $warnings)).'</div>'
                                    : '<div class="rounded-lg bg-success-50 p-3 text-sm text-success-800 dark:bg-success-500/10 dark:text-success-300">Colour contrast meets WCAG AA for readable text.</div>');
                            }),
                        ]),
                        Tab::make('Typography & shape')->label(__('Typography & shape'))->icon('heroicon-o-language')->schema([
                            Select::make('theme.heading_font')->label(__('Heading font'))->options(collect(ThemeService::HEADING_FONTS)->map(fn ($f) => $f[0]))->required()->live(),
                            Select::make('theme.body_font')->label(__('Body font'))->options(collect(ThemeService::BODY_FONTS)->map(fn ($f) => $f[0]))->required()->live(),
                            Slider::make('theme.base_size')->label(__('Base text size (px)'))->range(15, 20)->step(1)->live(onBlur: true)->tooltips(),
                            ToggleButtons::make('theme.radius')->label(__('Corner rounding'))->options(\App\Support\Ui::options(['none' => 'Square', 'sm' => 'Subtle', 'md' => 'Rounded', 'lg' => 'Soft']))->inline()->live(),
                            ToggleButtons::make('theme.button_style')->label(__('Buttons'))->options(\App\Support\Ui::options(['square' => 'Square', 'rounded' => 'Rounded', 'pill' => 'Pill']))->inline()->live(),
                            ToggleButtons::make('theme.density')->label(__('Section spacing'))->options(\App\Support\Ui::options(['compact' => 'Compact', 'comfortable' => 'Comfortable', 'spacious' => 'Spacious']))->inline()->live(),
                            ToggleButtons::make('theme.container')->label(__('Content width'))->options(\App\Support\Ui::options(['narrow' => 'Narrow', 'standard' => 'Standard', 'wide' => 'Wide']))->inline()->live(),
                        ]),
                        Tab::make('Light & dark mode')->label(__('Light & dark mode'))->icon('heroicon-o-moon')->schema([
                            ToggleButtons::make('theme.color_mode')->label(__('Default for visitors'))->inline()
                                ->options(\App\Support\ColorMode::MODES)
                                ->icons(['light' => 'heroicon-o-sun', 'dark' => 'heroicon-o-moon', 'system' => 'heroicon-o-computer-desktop'])
                                ->helperText(__('“System” follows the light or dark setting of each visitor’s phone or computer (recommended).')),
                            Toggle::make('theme.color_mode_switch')->label(__('Let visitors choose (sun/moon button in the header)'))
                                ->helperText(__('Their choice is remembered on their device and, for members, on their account.')),
                            Placeholder::make('dark_help')->hiddenLabel()->content(new HtmlString('<p class="text-sm text-gray-500">Dark colours are created automatically from your brand colours. Change them below only if you want a different look — leave empty for automatic. Use the <strong>Light / Dark</strong> buttons above the preview to check both. Upload a <em>Logo (dark backgrounds)</em> under “Logo &amp; icons” so an image logo stays readable in dark mode.</p>')),
                            Grid::make(3)->schema([
                                ColorPicker::make('theme.dark_background')->label(__('Dark background'))->placeholder(__('Automatic'))->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600),
                                ColorPicker::make('theme.dark_surface')->label(__('Dark cards'))->placeholder(__('Automatic'))->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600),
                                ColorPicker::make('theme.dark_text')->label(__('Dark-mode text'))->placeholder(__('Automatic'))->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600),
                            ]),
                        ]),
                        Tab::make('Header & footer')->label(__('Header & footer'))->icon('heroicon-o-window')->schema([
                            ToggleButtons::make('theme.header_style')->label(__('Header'))->options(\App\Support\Ui::options(['light' => 'Light', 'dark' => 'Dark']))->inline()->live(),
                            Toggle::make('theme.header_sticky')->label(__('Keep header visible while scrolling'))->live(),
                            ToggleButtons::make('theme.footer_style')->label(__('Footer'))->options(\App\Support\Ui::options(['dark' => 'Dark', 'light' => 'Light']))->inline()->live(),
                        ]),
                        Tab::make('Site identity')->label(__('Site identity'))->icon('heroicon-o-identification')->schema([
                            Section::make('Name')->heading(__('Name'))->compact()->columns(2)->schema([
                                TextInput::make('site.name')->label(__('Site name'))->required()->maxLength(80)->live(onBlur: true)
                                    ->helperText(__('Used in page titles, search results, emails and certificates.')),
                                TextInput::make('branding.short_name')->label(__('Short name'))->maxLength(30)->placeholder(__('Defaults to the site name'))->live(onBlur: true)
                                    ->helperText(__('Under the icon on phone home screens — up to 12 characters fit best.')),
                                TextInput::make('site.tagline')->label(__('Tagline'))->maxLength(140)->columnSpanFull(),
                                TextInput::make('branding.logo_text')->label(__('Display title'))->maxLength(60)->placeholder(__('Defaults to the site name'))->live(onBlur: true)
                                    ->helperText(__('Shown next to the built-in symbol when no logo image is uploaded.')),
                                TextInput::make('branding.logo_alt')->label(__('Logo text alternative'))->maxLength(120)->placeholder(__('Defaults to the site name'))
                                    ->helperText(__('Read by screen readers and search engines — usually just the site name.')),
                            ]),
                            Section::make('Logo')->heading(__('Logo'))->compact()->schema([
                                Grid::make(1)->schema([
                                    MediaPicker::make('branding.logo_id')->label(__('Logo for light backgrounds'))->live()
                                        ->helperText(__('Horizontal logo, PNG or WebP with a transparent background, at least 120 px tall (about 480 × 120).')),
                                    MediaPicker::make('branding.logo_dark_id')->label(__('Logo for dark backgrounds'))->live()
                                        ->helperText(__('Light-coloured version. Used in dark mode, on a dark header/footer and on the sign-in page.')),
                                ]),
                                ToggleButtons::make('branding.logo_height')->label(__('Logo size in the header'))->inline()->options(\App\Services\Branding\BrandingService::LOGO_HEIGHTS)->live(),
                            ]),
                            Section::make('Site icon')->heading(__('Site icon'))->compact()->schema([
                                MediaPicker::make('branding.favicon_id')->label(__('Square site icon'))->live()
                                    ->helperText(__('Square PNG, at least 512 × 512 px, ideally with a transparent background. Browser tabs, bookmarks, phone home screens and the future app icon are created from it automatically.')),
                                ColorPicker::make('branding.icon_background')->label(__('Icon background'))->regex('/^#[0-9a-fA-F]{6}$/')->live(debounce: 600)
                                    ->helperText(__('Fills the space around the icon on iPhone and Android home screens.')),
                            ]),
                            View::make('filament.branding-preview'),
                            Section::make('Installable app')->heading(__('Installable app (PWA)'))->compact()->schema([
                                Toggle::make('pwa.enabled')->label(__('Let visitors install the website as an app'))
                                    ->helperText(__('Adds the app to phone home screens and desktop app lists (Android, iPhone/iPad, Chrome/Edge), opens full screen and keeps recently read public pages for offline reading. Requires HTTPS. Switching off removes the app support from visitors’ browsers on their next visit.')),
                                Toggle::make('pwa.install_prompt')->label(__('Suggest installing the app on phones'))
                                    ->helperText(__('A small, dismissible card after a few page views. Visitors who choose “Not now” are not asked again for 30 days.')),
                                Placeholder::make('pwa_https')->hiddenLabel()->visible(fn () => ! request()->isSecure() && ! in_array(request()->getHost(), ['localhost', '127.0.0.1'], true))
                                    ->content(new HtmlString('<span class="text-sm text-warning-600">'.e(__('This address is not using HTTPS, so browsers will not install the app here. Enable SSL in DirectAdmin and use https:// in APP_URL.')).'</span>')),
                            ]),
                            Section::make('Sharing')->heading(__('Sharing'))->compact()->schema([
                                MediaPicker::make('branding.og_image_id')->label(__('Default social sharing image'))->helperText(__('1200×630 px, used when a page has no image.')),
                            ]),
                        ]),
                        Tab::make('Advanced')->label(__('Advanced'))->icon('heroicon-o-code-bracket')->schema([
                            Textarea::make('theme.custom_css')->label(__('Custom CSS'))->rows(10)->extraInputAttributes(['class' => 'font-mono text-xs'])
                                ->helperText(__('For small adjustments only. Design tokens are available as CSS variables, e.g. var(--color-accent).')),
                        ]),
                    ]),
                ]),
                Group::make()->columnSpan(['xl' => 7])->extraAttributes(['class' => 'oa-sticky-preview'])->schema([View::make('filament.theme-preview')]),
            ]),
        ];
    }
}
