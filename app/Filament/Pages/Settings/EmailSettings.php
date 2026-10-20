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
            Action::make('test')->label('Send test email')->icon('heroicon-o-paper-airplane')->color('gray')
                ->schema([TextInput::make('to')->email()->required()->default(fn () => auth()->user()->email)])
                ->action(function (array $data) {
                    MailConfigurator::apply();
                    try {
                        Mail::to($data['to'])->send(new TestMail);
                        Notification::make()->success()->title('Test email sent')->body('Check the inbox (and spam folder) of '.$data['to'].'.')->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Sending failed')->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }

    protected function components(): array
    {
        $smtp = fn (Get $get) => $get('mail.mailer') === 'smtp';

        return [
            Section::make('Outgoing email')
                ->description('On DirectAdmin, create a mailbox (e.g. noreply@yourdomain) under E-mail Manager and enter its details here. Port 465 with SSL is the usual choice.')
                ->columns(2)->schema([
                    Select::make('mail.mailer')->label('Sending method')->options(['smtp' => 'SMTP mailbox (recommended)', 'sendmail' => 'Server sendmail', 'log' => 'Do not send (write to log — testing only)'])
                        ->placeholder('Use .env configuration')->live()->columnSpanFull(),
                    TextInput::make('mail.host')->label('SMTP server')->placeholder('mail.yourdomain.com')->visible($smtp),
                    TextInput::make('mail.port')->numeric()->visible($smtp),
                    TextInput::make('mail.username')->visible($smtp),
                    TextInput::make('mail.password')->password()->revealable()->visible($smtp)
                        ->placeholder(fn (Get $get) => $get('mail.password_set') ? '•••••••• (saved — leave empty to keep)' : null),
                    Select::make('mail.encryption')->options(['ssl' => 'SSL (port 465)', 'tls' => 'STARTTLS (port 587)'])->visible($smtp),
                    TextInput::make('mail.from_address')->label('From address')->email(),
                    TextInput::make('mail.from_name')->label('From name'),
                ]),
            Section::make('Newsletter')->columns(2)->schema([
                Toggle::make('newsletter.enabled')->label('Newsletter signups enabled'),
                Toggle::make('newsletter.double_opt_in')->label('Require email confirmation (double opt-in)')->helperText('Recommended for GDPR compliance and deliverability.'),
                TextInput::make('newsletter.from_name')->label('Newsletter sender name'),
                TextInput::make('newsletter.from_email')->label('Newsletter sender email')->email(),
                TextInput::make('newsletter.send_rate_per_minute')->label('Emails per minute')->numeric()->minValue(1)->maxValue(200)
                    ->helperText('Keep within your hosting limits (often 100–500 per hour).'),
                Toggle::make('newsletter.track_opens')->label('Track opens and clicks'),
                TextInput::make('newsletter.signup_heading')->label('Signup heading')->columnSpanFull(),
                Textarea::make('newsletter.signup_text')->label('Signup text')->rows(2)->columnSpanFull(),
            ]),
        ];
    }
}
