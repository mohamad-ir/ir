<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Resources\Menus\MenuResource;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class HeaderFooterSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/header-footer';

    protected static ?string $navigationLabel = 'Header & footer';

    protected static ?string $title = 'Header & footer';

    protected static string|\UnitEnum|null $navigationGroup = 'Design';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window';

    protected static ?int $navigationSort = 5;

    protected function groups(): array
    {
        return ['header', 'footer'];
    }

    protected function permission(): string
    {
        return 'design.manage';
    }

    protected function components(): array
    {
        $menus = new HtmlString(e(__('Navigation links are managed in')).' <a class="text-primary-600 underline" href="'.MenuResource::getUrl().'">'.e(__('Design › Navigation menus')).'</a>.');

        return [
            Section::make('Header')->heading(__('Header'))->columns(2)->schema([
                Toggle::make('header.show_search')->label(__('Show search button')),
                Toggle::make('header.show_account')->label(__('Show sign-in / account link')),
                Placeholder::make('menus')->hiddenLabel()->content($menus)->columnSpanFull(),
            ]),
            Section::make('Announcement bar')->heading(__('Announcement bar'))->description(__('A slim bar above the header for news, e.g. a new course or webinar.'))->columns(2)->schema([
                Toggle::make('header.announcement_enabled')->label(__('Show announcement'))->columnSpanFull(),
                TextInput::make('header.announcement')->label(__('Message'))->maxLength(160),
                TextInput::make('header.announcement_url')->label(__('Link (optional)')),
            ]),
            Section::make('Footer')->heading(__('Footer'))->schema([
                Textarea::make('footer.about')->label(__('About text'))->rows(3),
                Toggle::make('footer.show_newsletter')->label(__('Show newsletter signup')),
                TextInput::make('footer.copyright')->label(__('Copyright line'))->helperText(__('Use {year} and {site} as placeholders.')),
                Placeholder::make('menus2')->hiddenLabel()->content($menus),
            ]),
        ];
    }
}
