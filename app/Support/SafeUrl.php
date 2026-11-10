<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Links typed by editors (menus, buttons, blocks, social profiles) may only be web addresses, page
 * paths, anchors, email or phone links — never "javascript:", "data:" or similar, which would run
 * script in the visitor's (or an administrator's) session.
 */
class SafeUrl
{
    public static function isSafe(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return true;
        }
        // Browsers ignore control characters and whitespace inside the scheme ("java\nscript:").
        $compact = preg_replace('/[\x00-\x20]+/', '', $url);
        if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $compact)) {
            return (bool) preg_match('~^(https?|mailto|tel):~i', $compact);
        }

        return ! str_starts_with($compact, '//') || (bool) filter_var('https:'.$compact, FILTER_VALIDATE_URL);
    }

    /** The URL when it is safe; otherwise "#". Relative values without a scheme stay as they are. */
    public static function href(?string $url): string
    {
        $url = trim((string) $url);

        return $url !== '' && self::isSafe($url) ? $url : '#';
    }

    /** Validation rule for link fields (an object, so form builders do not evaluate it as a callback). */
    public static function rule(): ValidationRule
    {
        return new class implements ValidationRule
        {
            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                if (! SafeUrl::isSafe(is_string($value) ? $value : null)) {
                    $fail(__('Use a web address (https://…), a path on this site (/…), an email (mailto:) or a phone (tel:) link.'));
                }
            }
        };
    }
}
