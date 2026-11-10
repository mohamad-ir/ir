<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Payments\StripeAdmin;
use App\Services\Payments\StripeException;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'email', 'name'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Order')->heading(__('Order'))->columnSpan(2)->columns(2)->schema([
                TextEntry::make('number'),
                TextEntry::make('status')->badge(),
                TextEntry::make('description')->columnSpanFull(),
                TextEntry::make('subtotal')->formatStateUsing(fn ($state, Order $record) => Money::format($state, $record->currency)),
                TextEntry::make('tax')->label(__('VAT / tax'))->formatStateUsing(fn ($state, Order $record) => Money::format($state, $record->currency)),
                TextEntry::make('total')->formatStateUsing(fn ($state, Order $record) => Money::format($state, $record->currency))->weight('bold'),
                TextEntry::make('refunded_amount')->label(__('Refunded'))->formatStateUsing(fn ($state, Order $record) => Money::format($state, $record->currency)),
                TextEntry::make('paid_at')->dateTime('j M Y H:i')->placeholder('—'),
                TextEntry::make('refunded_at')->dateTime('j M Y H:i')->placeholder('—'),
                TextEntry::make('consent_at')->label(__('Withdrawal waiver accepted'))->dateTime('j M Y H:i')->placeholder('—'),
                TextEntry::make('meta.customer_country')->label(__('Billing country'))->placeholder('—'),
                TextEntry::make('meta.tax_ids')->label(__('Customer VAT ID'))->placeholder('—'),
            ]),
            Section::make('Customer')->heading(__('Customer'))->columnSpan(1)->schema([
                TextEntry::make('name')->placeholder('—'),
                TextEntry::make('email')->copyable(),
                TextEntry::make('user.career_stage')->label(__('Career stage'))->formatStateUsing(fn (?string $state) => \App\Models\CareerStage::labelFor($state))->placeholder('—'),
                TextEntry::make('stripe_payment_intent_id')->label(__('Stripe payment'))->placeholder('—')->copyable()
                    ->url(fn (Order $record) => $record->stripeDashboardUrl(), shouldOpenInNewTab: true),
                TextEntry::make('receipt_url')->label(__('Receipt / invoice'))->placeholder('—')->formatStateUsing(fn () => 'Open')
                    ->url(fn (Order $record) => $record->invoice_pdf_url ?: $record->receipt_url, shouldOpenInNewTab: true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->searchable()->weight('medium')->description(fn (Order $record) => ($record->paid_at ?? $record->created_at)?->format('j M Y H:i')),
                TextColumn::make('email')->label(__('Customer'))->searchable()->description(fn (Order $record) => $record->name),
                TextColumn::make('description')->label(__('Item'))->wrap()->limit(60)
                    ->description(fn (Order $record) => __(['course' => 'Course', 'webinar' => 'Webinar ticket', 'membership' => 'Membership'][$record->type] ?? $record->type)),
                TextColumn::make('total')->formatStateUsing(fn ($state, Order $record) => Money::format($state, $record->currency))->alignEnd()->sortable()
                    ->description(fn (Order $record) => $record->tax ? __('incl. :amount tax', ['amount' => Money::format($record->tax, $record->currency)]) : null),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::options())->default('paid'),
                SelectFilter::make('type')->options(\App\Support\Ui::options(['course' => 'Courses', 'webinar' => 'Webinar tickets', 'membership' => 'Membership payments'])),
                Filter::make('date')->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([ViewAction::make()->iconButton(), self::refundAction()->iconButton()])
            ->emptyStateHeading(__('No orders yet'))
            ->emptyStateDescription(__('Course purchases, webinar tickets and membership payments appear here automatically.'));
    }

    public static function refundAction(): Action
    {
        return Action::make('refund')->label(__('Refund'))->icon('heroicon-o-arrow-uturn-left')->color('warning')
            ->visible(fn (Order $record) => $record->isPaid() && $record->stripe_payment_intent_id && (auth()->user()?->can('sales.manage') ?? false))
            ->schema(fn (Order $record) => [
                TextInput::make('amount')->label(__('Amount to refund'))->numeric()->step('0.01')->required()
                    ->prefix($record->currency)->default(Money::toMajor($record->total - $record->refunded_amount))
                    ->maxValue(($record->total - $record->refunded_amount) / 100)->minValue(0.01),
                Select::make('reason')->options(\App\Support\Ui::options(['requested_by_customer' => 'Requested by customer', 'duplicate' => 'Duplicate payment', 'fraudulent' => 'Fraudulent']))->default('requested_by_customer'),
            ])
            ->modalDescription(__('The money is returned to the customer’s original payment method. A full refund of a course or webinar ticket also removes the access it bought.'))
            ->action(function (Order $record, array $data) {
                try {
                    app(StripeAdmin::class)->refund($record, Money::toMinor($data['amount']), $data['reason'] ?? null);
                    Notification::make()->success()->title(__('Refund issued'))->send();
                } catch (StripeException $e) {
                    Notification::make()->danger()->title(__('Refund failed'))->body($e->getMessage())->persistent()->send();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
