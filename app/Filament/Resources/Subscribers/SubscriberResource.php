<?php

namespace App\Filament\Resources\Subscribers;

use App\Enums\SubscriberStatus;
use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static ?string $navigationLabel = 'Newsletter subscribers';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Audience';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getNavigationBadge(): ?string
    {
        return number_format(Subscriber::active()->count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make()->columns(2)->schema([
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('name'),
            Select::make('status')->options(SubscriberStatus::options())->default('subscribed')->required()
                ->helperText('Only add people as “Subscribed” if they have given consent.'),
            TextInput::make('source')->placeholder('e.g. conference sign-up sheet'),
            CheckboxList::make('interests')->options(Subscriber::INTERESTS)->columns(2)->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->searchable(['email', 'name'])->weight('medium')->description(fn (Subscriber $record) => $record->name)->copyable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('interests')->badge()->color('gray')->formatStateUsing(fn ($state) => Subscriber::INTERESTS[$state] ?? $state)->limitList(2),
                TextColumn::make('source')->toggleable(),
                TextColumn::make('confirmed_at')->label('Confirmed')->date('j M Y')->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('Signed up')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SubscriberStatus::options()),
                SelectFilter::make('interest')->options(Subscriber::INTERESTS)->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereJsonContains('interests', $data['value']) : $query),
                SelectFilter::make('source')->options(fn () => Subscriber::query()->whereNotNull('source')->distinct()->pluck('source', 'source')),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('unsubscribe')->label('Mark unsubscribed')->icon('heroicon-o-no-symbol')->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()])),
                DeleteBulkAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscribers::route('/'),
            'create' => Pages\CreateSubscriber::route('/create'),
            'edit' => Pages\EditSubscriber::route('/{record}/edit'),
        ];
    }
}
