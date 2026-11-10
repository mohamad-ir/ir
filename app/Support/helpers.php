<?php

use App\Support\SafeUrl;
use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('safe_href')) {
    /** A link typed by an editor, made safe for href="…" (see App\Support\SafeUrl). */
    function safe_href(?string $url): string
    {
        return SafeUrl::href($url);
    }
}
