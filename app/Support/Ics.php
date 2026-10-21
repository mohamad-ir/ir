<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** RFC 5545 calendar file for a single event (Outlook, Apple and Google Calendar). */
class Ics
{
    public static function event(string $uid, Carbon $start, Carbon $end, string $title, string $description = '', ?string $url = null, ?string $location = null): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.self::escape((string) setting('site.name', 'Website')).'//Webinars//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$end->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape($title),
            'DESCRIPTION:'.self::escape($description),
        ];
        if ($url) {
            $lines[] = 'URL:'.$url;
        }
        if ($location) {
            $lines[] = 'LOCATION:'.self::escape($location);
        }
        array_push($lines,
            'BEGIN:VALARM', 'TRIGGER:-PT15M', 'ACTION:DISPLAY', 'DESCRIPTION:'.self::escape($title), 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR');

        return implode("\r\n", array_map([self::class, 'fold'], $lines))."\r\n";
    }

    protected static function escape(string $text): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** Lines longer than 75 octets are folded with CRLF + space. */
    protected static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }
        $out = '';
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--; // never split a multibyte character
            }
            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
        }

        return $out.$line;
    }
}
