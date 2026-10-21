<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Optional Cloudflare Turnstile spam protection (configure keys in Settings › Security). */
class Turnstile
{
    public static function enabled(): bool
    {
        return filled(Settings::get('security.turnstile_site_key')) && filled(Settings::get('security.turnstile_secret'));
    }

    public static function verify(?string $token, ?string $ip): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            return (bool) Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => Settings::get('security.turnstile_secret'),
                'response' => $token,
                'remoteip' => $ip,
            ])->json('success');
        } catch (Throwable) {
            return true; // fail open so genuine users are not blocked when Cloudflare is unreachable
        }
    }
}
