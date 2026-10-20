<?php

namespace App\Services\Analytics;

/** Lightweight user-agent classification (device, browser, OS) and bot filtering. */
class UserAgent
{
    public static function isBot(?string $ua): bool
    {
        if (blank($ua)) {
            return true;
        }

        return (bool) preg_match('~bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|quora link|outbrain|pinterest|vkshare|w3c_validator|whatsapp|headless|lighthouse|pagespeed|gtmetrix|python-requests|curl|wget|httpclient|okhttp|go-http|axios|node-fetch|monitor|uptime|preview~i', $ua);
    }

    public static function device(?string $ua, ?int $width = null): string
    {
        if ($width) {
            return $width < 768 ? 'mobile' : ($width < 1100 ? 'tablet' : 'desktop');
        }
        if (preg_match('~ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))~i', (string) $ua)) {
            return 'tablet';
        }

        return preg_match('~mobi|iphone|ipod|android|blackberry|iemobile|opera mini~i', (string) $ua) ? 'mobile' : 'desktop';
    }

    public static function browser(?string $ua): string
    {
        return match (true) {
            (bool) preg_match('~Edg/~', (string) $ua) => 'Edge',
            (bool) preg_match('~OPR/|Opera~', (string) $ua) => 'Opera',
            (bool) preg_match('~SamsungBrowser~', (string) $ua) => 'Samsung Internet',
            (bool) preg_match('~Firefox/|FxiOS~', (string) $ua) => 'Firefox',
            (bool) preg_match('~Chrome/|CriOS~', (string) $ua) => 'Chrome',
            (bool) preg_match('~Safari/~', (string) $ua) => 'Safari',
            default => 'Other',
        };
    }

    public static function os(?string $ua): string
    {
        return match (true) {
            (bool) preg_match('~iPhone|iPad|iPod~', (string) $ua) => 'iOS',
            (bool) preg_match('~Android~', (string) $ua) => 'Android',
            (bool) preg_match('~Windows~', (string) $ua) => 'Windows',
            (bool) preg_match('~Mac OS X|Macintosh~', (string) $ua) => 'macOS',
            (bool) preg_match('~CrOS~', (string) $ua) => 'ChromeOS',
            (bool) preg_match('~Linux~', (string) $ua) => 'Linux',
            default => 'Other',
        };
    }
}
