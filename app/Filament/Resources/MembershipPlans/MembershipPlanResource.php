<?php

namespace App\Filament\Resources\MembershipPlans;

use App\Models\MembershipPlan;
use App\Support\Money;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MembershipPlanResource extends Resource
{
    protected static ?string $model = MembershipPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Membership plans';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()->columnSpan(['lg' => 2])->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')->required()->maxLength(80)->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, $record) => $record ? null : $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')->required()->maxLength(80)->alphaDash()->unique(ignoreRecord: true),
                ]),
                TextInput::make('tagline')->maxLength(160)->placeholder('Best value for exam preparation'),
                Repeater::make('features')->label('Benefits listed on the pricing card')->schema([TextInput::make('text')->hiddenLabel()->required()->maxLength(160)])
                    ->defaultItems(0)->reorderable()->addActionLabel('Add benefit'),
                Textarea::make('description')->label('Internal notes')->rows(2),
            ]),
            Section::make('Price')->columnSpan(['lg' => 1])->schema([
                Grid::make(2)->schema([
                    TextInput::make('price')->numeric()->minValue(0.5)->step('0.01')->required()
                        ->formatStateUsing(fn ($state) => Money::toMajor($state))
                        ->dehydrateStateUsing(fn ($state) => Money::toMinor($state)),
                    Select::make('currency')->options(array_combine(array_keys(Money::CURRENCIES), array_keys(Money::CURRENCIES)))
                        ->default(fn () => Settings::get('payments.currency', 'EUR'))->selectablePlaceholder(false)->required(),
                ]),
                ToggleButtons::make('interval')->label('Billed')->options(['month' => 'Monthly', 'year' => 'Yearly'])->inline()->default('month')->required(),
                TextInput::make('trial_days')->label('Free trial (days)')->numeric()->minValue(0)->maxValue(90)->default(0),
                Toggle::make('is_featured')->label('Highlight as “Most popular”'),
                Toggle::make('is_active')->label('Available for new members')->default(true)
                    ->helperText('Turning a plan off hides it; existing members keep their subscription.'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            Section::make()->columnSpanFull()->schema([
                \Filament\Forms\Components\Placeholder::make('note')->hiddenLabel()->content('Changing the price creates a new price in Stripe for new members; existing members keep their current price until you change it in Stripe.'),
            ])->visible(fn ($record) => (bool) $record?->stripe_price_id),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['subscriptions as members_count' => fn ($q) => $q->grantingAccess()]))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->weight('medium')->description(fn (MembershipPlan $record) => $record->tagline),
                TextColumn::make('price')->formatStateUsing(fn (MembershipPlan $record) => $record->priceLabel().' / '.$record->intervalLabel()),
                TextColumn::make('trial_days')->label('Trial')->formatStateUsing(fn ($state) => $state ? $state.' days' : '—'),
                TextColumn::make('members_count')->label('Active members')->alignEnd(),
                IconColumn::make('is_active')->label('Available')->boolean(),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()])
            ->emptyStateHeading('No membership plans yet')
            ->emptyStateDescription('Create a monthly and/or yearly plan. Plans appear on /membership and in the “Membership plans” page section.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipPlans::route('/'),
            'create' => Pages\CreateMembershipPlan::route('/create'),
            'edit' => Pages\EditMembershipPlan::route('/{record}/edit'),
        ];
    }
}
