<?php

namespace App\Support;

class Typography
{
    /** Escapes text and turns *words* into an accent-coloured highlight. */
    public static function highlight(?string $text): string
    {
        $escaped = e((string) $text);

        return preg_replace('/\*(.+?)\*/u', '<span class="text-accent-ink italic">$1</span>', $escaped);
    }
}
