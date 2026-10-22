<?php

namespace App\Filament\Resources\Banners;

use App\Models\Banner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Design';

    protected static ?string $navigationLabel = 'Banners & sliders';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()->columnSpan(['lg' => 2])->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120)->placeholder('Spring course launch')->columnSpanFull(),
                Select::make('placement')->label('Show at')->options(Banner::PLACEMENTS)->placeholder('Only where I add it in the page builder')
                    ->helperText('One slider per place is shown (the first active one). You can also place any banner in a page with the “Banner slider” section.')
                    ->columnSpanFull(),
                ToggleButtons::make('height')->options(Banner::HEIGHTS)->default('standard')->inline(),
                ToggleButtons::make('transition')->options(['slide' => 'Slide', 'fade' => 'Fade'])->default('slide')->inline(),
            ]),
            Section::make('Behaviour')->columnSpan(['lg' => 1])->schema([
                Toggle::make('is_active')->label('Active')->default(true),
                Toggle::make('autoplay')->label('Play automatically')->default(true)
                    ->helperText('Pauses on hover and for visitors who prefer reduced motion.'),
                TextInput::make('interval')->label('Seconds per slide')->numeric()->minValue(3)->maxValue(30)->default(6),
                Toggle::make('show_arrows')->label('Arrows')->default(true),
                Toggle::make('show_dots')->label('Dots')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('slides')->withSum('slides', 'clicks_count'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->weight('medium')->searchable()->description(fn (Banner $record) => $record->slides_count.' slides'),
                TextColumn::make('placement')->label('Show at')->formatStateUsing(fn ($state) => Banner::PLACEMENTS[$state] ?? $state)->placeholder('Page builder only')->badge(),
                TextColumn::make('slides_sum_clicks_count')->label('Clicks')->numeric()->alignEnd()->placeholder('0'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()])
            ->emptyStateHeading('No banners yet')
            ->emptyStateDescription('Create a banner, add one or more image slides with links, and choose where it appears.');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\SlidesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
