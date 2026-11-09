<?php

namespace App\Filament\Resources\Banners\RelationManagers;

use App\Filament\Forms\MediaPicker;
use App\Models\BannerSlide;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SlidesRelationManager extends RelationManager
{
    use \App\Filament\Concerns\TranslatesRelationManagerLabels;

    protected static string $relationship = 'slides';

    protected static ?string $modelLabel = 'slide';

    protected static ?string $pluralModelLabel = 'slides';

    protected static ?string $title = 'Slides';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-photo';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->slides()->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                MediaPicker::make('media_id')->label(__('Image (desktop)'))->required()->helperText(__('Wide image, about 2400 × 900 px.')),
                MediaPicker::make('mobile_media_id')->label(__('Image for phones (optional)'))->helperText(__('Taller crop, about 1080 × 1350 px.')),
            ]),
            TextInput::make('alt')->label(__('Image description (alt text)'))->maxLength(200),
            Section::make('Text on the slide (optional)')->heading(__('Text on the slide (optional)'))->compact()->columns(2)->schema([
                TextInput::make('eyebrow')->label(__('Small label'))->maxLength(60),
                TextInput::make('title')->maxLength(120),
                Textarea::make('text')->rows(2)->maxLength(300)->columnSpanFull(),
                ToggleButtons::make('text_position')->label(__('Text position'))->options(\App\Support\Ui::options(['left' => 'Left', 'center' => 'Centre', 'right' => 'Right']))->default('left')->inline(),
                ToggleButtons::make('overlay')->label(__('Shade behind text'))->options(\App\Support\Ui::options(['dark' => 'Dark', 'light' => 'Light', 'none' => 'None']))->default('dark')->inline(),
            ]),
            Section::make('Link')->heading(__('Link'))->compact()->columns(2)->schema([
                TextInput::make('link_url')->label(__('Link'))->placeholder(__('/courses/oct-masterclass or https://…'))->maxLength(500)
                    ->rule('regex:~^(/|https?://|mailto:|tel:)~i')->validationMessages(\App\Support\Ui::options(['regex' => 'Start with / for a page on this site, or https:// for another website.']))
                    ->helperText(__('Internal page (/…) or external website (https://…).'))->columnSpanFull(),
                TextInput::make('button_label')->label(__('Button text'))->maxLength(60)->helperText(__('Leave empty to make the whole slide clickable.')),
                Toggle::make('new_tab')->label(__('Open in a new tab'))->inline(false),
            ]),
            Section::make('Schedule')->heading(__('Schedule'))->compact()->columns(3)->schema([
                Toggle::make('is_active')->label(__('Show this slide'))->default(true)->inline(false),
                DateTimePicker::make('starts_at')->label(__('From'))->seconds(false)->native(false),
                DateTimePicker::make('ends_at')->label(__('Until'))->seconds(false)->native(false)->after('starts_at'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('preview')->label('')->getStateUsing(fn (BannerSlide $record) => $record->media?->thumbUrl())->imageWidth(120)->imageHeight(45)->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('title')->placeholder(__('Image only'))->wrap()->description(fn (BannerSlide $record) => $record->link_url),
                TextColumn::make('schedule')->badge()->getStateUsing(fn (BannerSlide $record) => $record->scheduleLabel())
                    ->color(fn (string $state) => $state === 'Showing' || str_starts_with($state, 'Until') ? 'success' : 'gray'),
                TextColumn::make('clicks_count')->label(__('Clicks'))->numeric()->alignEnd(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('Add slide'))->icon('heroicon-m-plus')->slideOver()->modalWidth('3xl')
                    ->mutateDataUsing(fn (array $data) => [...$data, 'sort_order' => (int) $this->getOwnerRecord()->slides()->max('sort_order') + 1]),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('3xl')->iconButton(),
                ReplicateAction::make()->iconButton()->excludeAttributes(['clicks_count']),
                DeleteAction::make()->iconButton(),
            ])
            ->emptyStateHeading(__('No slides yet'))
            ->emptyStateDescription(__('Add an image slide. Several slides become a slideshow; drag to reorder.'));
    }
}
