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
        return ['theme', 'branding'];
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
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preset')->label('Apply a colour preset')->icon('heroicon-o-sparkles')->color('gray')
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
                    Tabs::make()->tabs([
                        Tab::make('Colours')->icon('heroicon-o-swatch')->schema([
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
                        Tab::make('Typography & shape')->icon('heroicon-o-language')->schema([
                            Select::make('theme.heading_font')->label('Heading font')->options(collect(ThemeService::HEADING_FONTS)->map(fn ($f) => $f[0]))->required()->live(),
                            Select::make('theme.body_font')->label('Body font')->options(collect(ThemeService::BODY_FONTS)->map(fn ($f) => $f[0]))->required()->live(),
                            Slider::make('theme.base_size')->label('Base text size (px)')->range(15, 20)->step(1)->live(onBlur: true)->tooltips(),
                            ToggleButtons::make('theme.radius')->label('Corner rounding')->options(['none' => 'Square', 'sm' => 'Subtle', 'md' => 'Rounded', 'lg' => 'Soft'])->inline()->live(),
                            ToggleButtons::make('theme.button_style')->label('Buttons')->options(['square' => 'Square', 'rounded' => 'Rounded', 'pill' => 'Pill'])->inline()->live(),
                            ToggleButtons::make('theme.density')->label('Section spacing')->options(['compact' => 'Compact', 'comfortable' => 'Comfortable', 'spacious' => 'Spacious'])->inline()->live(),
                            ToggleButtons::make('theme.container')->label('Content width')->options(['narrow' => 'Narrow', 'standard' => 'Standard', 'wide' => 'Wide'])->inline()->live(),
                        ]),
                        Tab::make('Header & footer')->icon('heroicon-o-window')->schema([
                            ToggleButtons::make('theme.header_style')->label('Header')->options(['light' => 'Light', 'dark' => 'Dark'])->inline()->live(),
                            Toggle::make('theme.header_sticky')->label('Keep header visible while scrolling')->live(),
                            ToggleButtons::make('theme.footer_style')->label('Footer')->options(['dark' => 'Dark', 'light' => 'Light'])->inline()->live(),
                        ]),
                        Tab::make('Logo & icons')->icon('heroicon-o-photo')->schema([
                            TextInput::make('branding.logo_text')->label('Logo text')->placeholder('Defaults to the website name'),
                            MediaPicker::make('branding.logo_id')->label('Logo (light backgrounds)')->helperText('SVG-like PNG with transparent background, about 400×100 px.'),
                            MediaPicker::make('branding.logo_dark_id')->label('Logo (dark backgrounds)'),
                            MediaPicker::make('branding.favicon_id')->label('Browser icon (favicon)')->helperText('Square PNG, at least 512×512 px.'),
                            MediaPicker::make('branding.og_image_id')->label('Default social sharing image')->helperText('1200×630 px, used when a page has no image.'),
                        ]),
                        Tab::make('Advanced')->icon('heroicon-o-code-bracket')->schema([
                            Textarea::make('theme.custom_css')->label('Custom CSS')->rows(10)->extraInputAttributes(['class' => 'font-mono text-xs'])
                                ->helperText('For small adjustments only. Design tokens are available as CSS variables, e.g. var(--color-accent).'),
                        ]),
                    ]),
                ]),
                Group::make()->columnSpan(['xl' => 7])->schema([View::make('filament.theme-preview')]),
            ]),
        ];
    }
}
