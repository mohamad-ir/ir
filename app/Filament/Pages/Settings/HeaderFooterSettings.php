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
        $menus = new HtmlString('Navigation links are managed in <a class="text-primary-600 underline" href="'.MenuResource::getUrl().'">Design › Navigation menus</a>.');

        return [
            Section::make('Header')->columns(2)->schema([
                Toggle::make('header.show_search')->label('Show search button'),
                Toggle::make('header.show_account')->label('Show sign-in / account link'),
                Placeholder::make('menus')->hiddenLabel()->content($menus)->columnSpanFull(),
            ]),
            Section::make('Announcement bar')->description('A slim bar above the header for news, e.g. a new course or webinar.')->columns(2)->schema([
                Toggle::make('header.announcement_enabled')->label('Show announcement')->columnSpanFull(),
                TextInput::make('header.announcement')->label('Message')->maxLength(160),
                TextInput::make('header.announcement_url')->label('Link (optional)'),
            ]),
            Section::make('Footer')->schema([
                Textarea::make('footer.about')->label('About text')->rows(3),
                Toggle::make('footer.show_newsletter')->label('Show newsletter signup'),
                TextInput::make('footer.copyright')->label('Copyright line')->helperText('Use {year} and {site} as placeholders.'),
                Placeholder::make('menus2')->hiddenLabel()->content($menus),
            ]),
        ];
    }
}
