<?php

namespace App\Filament\Pages\Settings;

use App\Mail\TestMail;
use App\Support\MailConfigurator;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailSettings extends SettingsPage
{
    protected static ?string $slug = 'settings/email';

    protected static ?string $navigationLabel = 'Email & newsletter';

    protected static ?string $title = 'Email & newsletter settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-at-symbol';

    protected static ?int $navigationSort = 4;

    protected function groups(): array
    {
        return ['mail', 'newsletter'];
    }

    protected function permission(): string
    {
        return 'settings.manage';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')->label(__('Send test email'))->icon('heroicon-o-paper-airplane')->color('gray')
                ->schema([TextInput::make('to')->email()->required()->default(fn () => auth()->user()->email)])
                ->action(function (array $data) {
                    MailConfigurator::apply();
                    try {
                        Mail::to($data['to'])->send(new TestMail);
                        Notification::make()->success()->title(__('Test email sent'))->body(__('Check the inbox (and spam folder) of :email.', ['email' => $data['to']]))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('Sending failed'))->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }

    protected function components(): array
    {
        $smtp = fn (Get $get) => $get('mail.mailer') === 'smtp';

        return [
            Section::make('Outgoing email')->heading(__('Outgoing email'))
                ->description(__('On DirectAdmin, create a mailbox (e.g. noreply@yourdomain) under E-mail Manager and enter its details here. Port 465 with SSL is the usual choice.'))
                ->columns(2)->schema([
                    Select::make('mail.mailer')->label(__('Sending method'))->options(\App\Support\Ui::options(['smtp' => 'SMTP mailbox (recommended)', 'sendmail' => 'Server sendmail', 'log' => 'Do not send (write to log — testing only)']))
                        ->placeholder(__('Use .env configuration'))->live()->columnSpanFull(),
                    TextInput::make('mail.host')->label(__('SMTP server'))->placeholder('mail.yourdomain.com')->visible($smtp),
                    TextInput::make('mail.port')->numeric()->visible($smtp),
                    TextInput::make('mail.username')->visible($smtp),
                    TextInput::make('mail.password')->password()->revealable()->visible($smtp)
                        ->placeholder(fn (Get $get) => $get('mail.password_set') ? __('•••••••• (saved — leave empty to keep)') : null),
                    Select::make('mail.encryption')->options(\App\Support\Ui::options(['ssl' => 'SSL (port 465)', 'tls' => 'STARTTLS (port 587)']))->visible($smtp),
                    TextInput::make('mail.from_address')->label(__('From address'))->email(),
                    TextInput::make('mail.from_name')->label(__('From name')),
                ]),
            Section::make('Newsletter')->heading(__('Newsletter'))->columns(2)->schema([
                Toggle::make('newsletter.enabled')->label(__('Newsletter signups enabled')),
                Toggle::make('newsletter.double_opt_in')->label(__('Require email confirmation (double opt-in)'))->helperText(__('Recommended for GDPR compliance and deliverability.')),
                TextInput::make('newsletter.from_name')->label(__('Newsletter sender name')),
                TextInput::make('newsletter.from_email')->label(__('Newsletter sender email'))->email(),
                TextInput::make('newsletter.send_rate_per_minute')->label(__('Emails per minute'))->numeric()->minValue(1)->maxValue(200)
                    ->helperText(__('Keep within your hosting limits (often 100–500 per hour).')),
                Toggle::make('newsletter.track_opens')->label(__('Track opens and clicks')),
                TextInput::make('newsletter.signup_heading')->label(__('Signup heading'))->columnSpanFull(),
                Textarea::make('newsletter.signup_text')->label(__('Signup text'))->rows(2)->columnSpanFull(),
            ]),
        ];
    }
}
