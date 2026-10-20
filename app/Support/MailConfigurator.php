<?php

namespace App\Support;

use Throwable;

/** Applies SMTP settings saved in the dashboard on top of .env mail configuration. */
class MailConfigurator
{
    public static function apply(): void
    {
        try {
            $mail = Settings::group('mail');
        } catch (Throwable) {
            return;
        }

        if (($mail['mailer'] ?? null) === 'smtp' && filled($mail['host'] ?? null)) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $mail['host'],
                'mail.mailers.smtp.port' => (int) ($mail['port'] ?? 465),
                'mail.mailers.smtp.username' => $mail['username'] ?? null,
                'mail.mailers.smtp.password' => $mail['password'] ?? null,
                'mail.mailers.smtp.scheme' => ($mail['encryption'] ?? 'ssl') === 'ssl' ? 'smtps' : 'smtp',
            ]);
        } elseif (in_array($mail['mailer'] ?? null, ['sendmail', 'log'], true)) {
            config(['mail.default' => $mail['mailer']]);
        }

        if (filled($mail['from_address'] ?? null)) {
            config(['mail.from.address' => $mail['from_address'], 'mail.from.name' => $mail['from_name'] ?: Settings::get('site.name')]);
        } else {
            config(['mail.from.name' => Settings::get('site.name')]);
        }
    }
}
