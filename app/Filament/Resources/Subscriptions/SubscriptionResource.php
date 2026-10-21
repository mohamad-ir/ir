<?php

namespace App\Filament\Resources\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Payments\StripeAdmin;
use App\Services\Payments\StripeException;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'membership';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Subscription::grantingAccess()->count();

        return $count ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'plan']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Member')->searchable()->description(fn (Subscription $record) => $record->user?->email),
                TextColumn::make('plan.name')->label('Plan')->placeholder('—')->description(fn (Subscription $record) => $record->priceLabel()),
                TextColumn::make('status')->badge()->sortable()
                    ->description(fn (Subscription $record) => $record->cancel_at_period_end && $record->grantsAccess() ? 'Ends at period end' : null),
                TextColumn::make('current_period_end')->label('Renews / ends')->date('j M Y')->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('Member since')->date('j M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SubscriptionStatus::options())->multiple()->default(SubscriptionStatus::ACCESS),
                SelectFilter::make('membership_plan_id')->label('Plan')->relationship('plan', 'name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('stripe')->label('Open in Stripe')->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Subscription $record) => $record->stripeDashboardUrl(), shouldOpenInNewTab: true),
                    Action::make('cancel_end')->label('Cancel at period end')->icon('heroicon-o-pause-circle')->color('warning')->requiresConfirmation()
                        ->visible(fn (Subscription $record) => $record->grantsAccess() && ! $record->cancel_at_period_end && auth()->user()?->can('sales.manage'))
                        ->modalDescription('The member keeps access until the end of the paid period, then the membership stops. No refund is made.')
                        ->action(fn (Subscription $record) => self::run(fn () => app(StripeAdmin::class)->cancelSubscription($record), 'Membership will end at the period end')),
                    Action::make('resume')->label('Resume renewal')->icon('heroicon-o-play-circle')->requiresConfirmation()
                        ->visible(fn (Subscription $record) => $record->grantsAccess() && $record->cancel_at_period_end && auth()->user()?->can('sales.manage'))
                        ->action(fn (Subscription $record) => self::run(fn () => app(StripeAdmin::class)->resumeSubscription($record), 'Membership will renew again')),
                    Action::make('cancel_now')->label('Cancel immediately')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()
                        ->visible(fn (Subscription $record) => $record->grantsAccess() && auth()->user()?->can('sales.manage'))
                        ->modalDescription('Access ends now. Refund the last payment separately under Orders if appropriate.')
                        ->action(fn (Subscription $record) => self::run(fn () => app(StripeAdmin::class)->cancelSubscription($record, true), 'Membership cancelled')),
                ]),
            ])
            ->emptyStateHeading('No members yet')
            ->emptyStateDescription('Memberships bought through the website appear here, kept in sync with Stripe automatically.');
    }

    protected static function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (StripeException $e) {
            Notification::make()->danger()->title('Stripe error')->body($e->getMessage())->persistent()->send();
        }
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubscriptions::route('/')];
    }
}
