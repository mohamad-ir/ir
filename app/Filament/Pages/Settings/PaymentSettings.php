<?php

namespace App\Filament\Pages\Settings;

use App\Models\Page;
use App\Services\Payments\Fulfillment;
use App\Services\Payments\StripeClient;
use App\Services\Payments\StripeException;
use App\Services\Payments\StripeReferences;
use App\Support\Money;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class PaymentSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/payments';

    protected static ?string $navigationLabel = 'Payment settings';

    protected static ?string $title = 'Payments & membership settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 9;

    protected function groups(): array
    {
        return ['payments', 'membership'];
    }

    protected function permission(): string
    {
        return 'sales.manage';
    }

    protected function mutateStateBeforeSave(array $state): array
    {
        if (($state['payments']['enabled'] ?? false) && blank($state['payments']['stripe_secret_key'] ?? null) && blank(Settings::get('payments.stripe_secret_key'))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['data.payments.stripe_secret_key' => 'Add your Stripe secret key before switching payments on.']);
        }

        return $state;
    }

    protected function afterSave(): void
    {
        if (StripeReferences::syncMode()) {
            Notification::make()->warning()->title('Stripe mode changed')
                ->body('You switched between test and live keys. Customers, products and prices will be created again in the new mode. Remember to set up the webhook for this mode too.')
                ->persistent()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')->label('Test connection')->icon('heroicon-o-signal')->color('gray')
                ->action(function () {
                    try {
                        $stripe = new StripeClient;
                        $account = $stripe->get('account');
                        $mode = $stripe->isTestMode() ? 'TEST mode' : 'LIVE mode';
                        $name = $account['settings']['dashboard']['display_name'] ?? $account['business_profile']['name'] ?? $account['id'];
                        $charges = ($account['charges_enabled'] ?? true) ? 'Payments enabled.' : 'Stripe has not yet enabled payments on this account — finish the account activation in Stripe.';
                        Notification::make()->success()->title("Connected to {$name} ({$mode})")->body($charges)->persistent()->send();
                    } catch (StripeException $e) {
                        Notification::make()->danger()->title('Connection failed')->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('webhook')->label('Set up webhook automatically')->icon('heroicon-o-bolt')
                ->requiresConfirmation()
                ->modalDescription(fn () => 'Creates a Stripe webhook that sends payment events to '.route('stripe.webhook').' and saves its signing secret here. Your website must be reachable on the internet at this address (it will not work on a local computer).')
                ->action(function () {
                    try {
                        $stripe = new StripeClient;
                        $endpoint = $stripe->post('webhook_endpoints', [
                            'url' => route('stripe.webhook'),
                            'enabled_events' => Fulfillment::EVENTS,
                            'description' => Settings::get('site.name').' website',
                            'api_version' => StripeClient::API_VERSION,
                        ]);
                        if (($old = Settings::get('payments.webhook_endpoint_id')) && $old !== $endpoint['id']) {
                            try {
                                $stripe->delete('webhook_endpoints/'.$old);
                            } catch (StripeException) {
                                // The previous endpoint may belong to the other mode or already be gone.
                            }
                        }
                        Settings::set('payments.stripe_webhook_secret', $endpoint['secret'], true);
                        Settings::set('payments.webhook_endpoint_id', $endpoint['id']);
                        $this->mount();
                        Notification::make()->success()->title('Webhook created')->body('Stripe will now notify the website about payments, renewals, cancellations and refunds.')->send();
                    } catch (StripeException $e) {
                        Notification::make()->danger()->title('Could not create the webhook')->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }

    protected function components(): array
    {
        $pages = fn () => Page::published()->orderBy('title')->get()->mapWithKeys(fn ($p) => ['/'.$p->slug => $p->title])->all();

        return [
            Section::make('Stripe')
                ->description(new HtmlString('Payments are processed by <a href="https://stripe.com" target="_blank" class="underline">Stripe</a> on its own secure checkout page, so card details never reach this server. Create a Stripe account, then copy the <strong>secret key</strong> from Developers › API keys. Start with a <strong>test key</strong> (sk_test_…) and use the card 4242 4242 4242 4242 to try a purchase.'))
                ->columns(2)->schema([
                    Toggle::make('payments.enabled')->label('Accept payments on the website')->columnSpanFull()
                        ->helperText('While off, prices are shown but buttons say “opening soon”. Free courses and free webinars work either way.'),
                    TextInput::make('payments.stripe_secret_key')->label('Secret key')->password()->revealable()
                        ->placeholder(fn (Get $get) => $get('payments.stripe_secret_key_set') ? '•••••••• (saved — leave empty to keep)' : 'sk_test_… or sk_live_…')
                        ->rule('nullable')->regex('/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/')
                        ->validationMessages(['regex' => 'This does not look like a Stripe secret key (sk_test_… or sk_live_…).']),
                    TextInput::make('payments.stripe_webhook_secret')->label('Webhook signing secret')->password()->revealable()
                        ->placeholder(fn (Get $get) => $get('payments.stripe_webhook_secret_set') ? '•••••••• (saved — leave empty to keep)' : 'whsec_…')
                        ->helperText('Filled automatically by “Set up webhook automatically”.'),
                    Placeholder::make('webhook_url')->label('Webhook address')->columnSpanFull()
                        ->content(fn () => new HtmlString('<code class="text-sm">'.e(route('stripe.webhook')).'</code><br><span class="text-xs text-gray-500">Events: '.e(implode(', ', Fulfillment::EVENTS)).'</span>')),
                ]),
            Section::make('Prices & tax')->columns(2)->schema([
                Select::make('payments.currency')->label('Default currency for new courses, tickets and plans')->options(Money::CURRENCIES)->selectablePlaceholder(false),
                Toggle::make('payments.prices_include_tax')->label('Prices include VAT')->inline(false)
                    ->helperText('Recommended for consumers in Europe: the price shown is the price paid.'),
                Toggle::make('payments.automatic_tax')->label('Calculate VAT automatically with Stripe Tax')
                    ->helperText('Requires Stripe Tax to be activated (Stripe dashboard › Tax) and your tax registrations to be added there.'),
                Toggle::make('payments.collect_tax_id')->label('Let businesses enter a VAT number')
                    ->helperText('Shown on the invoice; enables reverse charge with Stripe Tax.'),
                Toggle::make('payments.allow_promotion_codes')->label('Allow discount codes at checkout')
                    ->helperText('Create codes in Stripe › Product catalogue › Coupons.'),
                Toggle::make('payments.invoice_one_time')->label('Create a formal invoice for course and ticket purchases')
                    ->helperText('Stripe charges a small fee per invoice. Membership payments always get an invoice.'),
            ]),
            Section::make('Legal')->columns(2)->schema([
                Select::make('payments.terms_url')->label('Terms of sale page')->options($pages)->searchable(),
                Select::make('payments.refund_url')->label('Refund policy page')->options($pages)->searchable(),
                Toggle::make('payments.withdrawal_waiver')->label('Ask EU/UK buyers to waive the 14-day withdrawal right for immediate access')->columnSpanFull()
                    ->helperText('Required to start access immediately to digital content sold to consumers in the EU and UK. Keep on unless your adviser says otherwise.'),
            ]),
            Section::make('Membership page')->schema([
                TextInput::make('membership.heading')->label('Heading'),
                Textarea::make('membership.intro')->label('Introduction')->rows(2),
                Repeater::make('membership.faq')->label('Questions & answers')->schema([
                    TextInput::make('question')->required(),
                    Textarea::make('answer')->rows(2)->required(),
                ])->collapsible()->collapsed()->itemLabel(fn (array $state) => $state['question'] ?? null)->defaultItems(0),
            ]),
        ];
    }
}
