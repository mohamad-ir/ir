<?php

namespace App\Support;

use Illuminate\Support\Str;

class Html
{
    /** Converts rich HTML into clean plain text for search, excerpts and meta tags. */
    public static function text(string|array|null $html): string
    {
        if (blank($html)) {
            return '';
        }
        if (is_array($html)) {
            $html = (string) RichText::render($html);
        }

        $html = preg_replace('~<(script|style)[^>]*>.*?</\1>~is', ' ', $html);
        $html = preg_replace('~<(br|/p|/h[1-6]|/li|/tr|/div|/blockquote)[^>]*>~i', "$0\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace(["/[ \t\x{00A0}]+/u", "/\n\s*\n+/"], [' ', "\n"], $text));
    }

    /** First few top-level paragraphs of rendered HTML, for members-only teasers. */
    public static function teaser(string $html, int $paragraphs = 2): string
    {
        preg_match_all('~<p\b[^>]*>.*?</p>~is', $html, $m);

        return implode('', array_slice($m[0], 0, $paragraphs));
    }

    public static function excerpt(?string $html, int $limit = 180): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', static::text($html)), $limit);
    }
}
