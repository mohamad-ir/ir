<?php

namespace App\Filament\Pages\Settings;

use App\Services\Search\SearchIndexer;
use App\Services\Seo\SitemapGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class SeoSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/seo';

    protected static ?string $navigationLabel = 'SEO settings';

    protected static ?string $title = 'SEO settings';

    protected static string|\UnitEnum|null $navigationGroup = 'SEO';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?int $navigationSort = 1;

    protected function groups(): array
    {
        return ['seo'];
    }

    protected function permission(): string
    {
        return 'seo.manage';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sitemap')->label('View sitemap')->icon('heroicon-o-map')->color('gray')->url(route('sitemap'), true),
            Action::make('rebuild')->label('Rebuild search index & sitemap')->icon('heroicon-o-arrow-path')->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $n = app(SearchIndexer::class)->rebuild(SearchIndexer::MODELS);
                    SitemapGenerator::flush();
                    Notification::make()->success()->title("Indexed {$n} items")->send();
                }),
        ];
    }

    protected function components(): array
    {
        return [
            Section::make('Titles')->columns(2)->schema([
                TextInput::make('seo.title_template')->label('Page title template')->helperText('%title% = page title, %site% = website name.'),
                TextInput::make('seo.home_title')->label('Homepage title')->placeholder('Defaults to “Site name — tagline”'),
            ]),
            Section::make('Google & Bing')->columns(2)->schema([
                TextInput::make('seo.google_verification')->label('Google Search Console verification code')->helperText('Only the content="…" value of the HTML tag method.'),
                TextInput::make('seo.bing_verification')->label('Bing Webmaster verification code'),
                Placeholder::make('submit')->hiddenLabel()->columnSpanFull()->content(new HtmlString('Submit your sitemap in Search Console: <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-white/10">'.e(route('sitemap')).'</code>')),
            ]),
            Section::make('Social sharing')->columns(2)->schema([
                TextInput::make('seo.twitter_handle')->label('X / Twitter handle')->prefix('@'),
                Toggle::make('seo.schema_person')->label('Publish “Physician” structured data for the doctor profile')->inline(false),
            ]),
            Section::make('Indexing')->schema([
                Toggle::make('seo.default_noindex')->label('Hide the whole website from search engines')
                    ->helperText('Use only while preparing the site before launch.'),
                Textarea::make('seo.robots_extra')->label('Additional robots.txt rules')->rows(3)->placeholder("User-agent: GPTBot\nDisallow: /"),
            ]),
        ];
    }
}
