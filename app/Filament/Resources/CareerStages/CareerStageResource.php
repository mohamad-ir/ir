<?php

namespace App\Filament\Resources\CareerStages;

use App\Filament\Concerns\TranslatesResourceLabels;
use App\Models\CareerStage;
use App\Support\Ui;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** The career stages members choose from at registration and in their profile. */
class CareerStageResource extends Resource
{
    use TranslatesResourceLabels;

    protected static ?string $model = CareerStage::class;

    protected static ?string $modelLabel = 'career stage';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static ?string $navigationLabel = 'Career stages';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(80)->columnSpanFull()
                ->placeholder(__('e.g. Orthoptist'))
                ->helperText(__('Shown to members in English on the registration form and in their profile.')),
            TextInput::make('key')->label(__('Key'))->maxLength(40)->alphaDash()->notRegex('/^\d+$/')->unique(ignoreRecord: true)
                ->disabledOn('edit')->dehydrated(fn (string $operation) => $operation === 'create')
                ->placeholder(__('Created from the name'))
                ->helperText(__('Stored with each member. It cannot be changed later, so renaming a stage never affects members.')),
            Select::make('color')->label(__('Badge colour'))->options(Ui::options(CareerStage::COLORS))->default('gray')->required()->native(false),
            Toggle::make('is_active')->label(__('Members can choose it'))->default(true)->columnSpanFull()
                ->helperText(__('Switched off: hidden from the forms, but members who already chose it keep it.')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            // Reordering saves with one query (no model events), so refresh the cached list here.
            ->afterReordering(fn () => CareerStage::flush())
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label(__('Name'))->weight('medium')->searchable()
                    ->formatStateUsing(fn (string $state) => __($state))
                    ->description(fn (CareerStage $record) => $record->key),
                TextColumn::make('color')->label(__('Badge'))->badge()
                    ->formatStateUsing(fn (CareerStage $record) => __($record->name))
                    ->color(fn (string $state) => $state),
                TextColumn::make('members')->label(__('Members'))->alignEnd()
                    ->state(fn (CareerStage $record) => $record->usersCount())->numeric(),
                ToggleColumn::make('is_active')->label(__('Members can choose it')),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton()
                    ->before(function (CareerStage $record, DeleteAction $action) {
                        if ($count = $record->usersCount()) {
                            Notification::make()->danger()
                                ->title(__('This stage is in use'))
                                ->body(__(':count members have chosen it. Switch it off instead, so they keep it.', ['count' => $count]))
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->emptyStateHeading(__('No career stages'));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCareerStages::route('/')];
    }
}
