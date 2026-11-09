<?php

/*
 * First-run helper for shared hosting and local tools (DirectAdmin, Herd, Valet…):
 * makes sure .env exists, holds the settings of .env.example and has an APP_KEY,
 * so the web installer can start.
 *
 * Browsers send several requests at once on the first visit (page, favicon,
 * prefetch). Writing .env is therefore done under an exclusive lock and through a
 * temporary file that replaces .env in one step, so no request can ever see — or
 * save — a half-written file (which used to leave a .env with only APP_KEY).
 * A .env damaged that way is repaired: settings missing from it are taken from
 * .env.example; values already in .env are never changed.
 */

(static function (): void {
    $base = dirname(__DIR__);
    $env = $base.'/.env';
    $example = $base.'/.env.example';

    $complete = static function (string $contents): bool {
        return (bool) preg_match('/^APP_KEY=base64:.+$/m', $contents)
            && (bool) preg_match('/^DB_CONNECTION=/m', $contents);
    };

    // Fast path for every normal request: nothing to do.
    if (is_file($env) && $complete((string) @file_get_contents($env))) {
        return;
    }

    $lock = @fopen(sys_get_temp_dir().'/oct-academy-env-'.md5($base).'.lock', 'c');
    if ($lock) {
        flock($lock, LOCK_EX);
    }

    try {
        $current = is_file($env) ? (string) @file_get_contents($env) : '';
        if ($complete($current) || (is_file($env) && ! is_writable($env)) || (! is_file($env) && ! is_writable($base))) {
            return;
        }

        $contents = $current;
        $installed = is_file($base.'/storage/app/installed.lock') || preg_match('/^APP_INSTALLED=(true|1)\b/mi', $current);

        // Before installation only: add every setting of .env.example that .env does not define yet.
        // An installed site's .env is never extended — at most its missing APP_KEY is generated.
        if (! $installed && is_file($example)) {
            $defined = preg_match_all('/^([A-Z0-9_]+)=/m', $current, $m) ? array_flip($m[1]) : [];
            $missing = [];
            foreach (preg_split('/\R/', (string) file_get_contents($example)) as $line) {
                if (preg_match('/^([A-Z0-9_]+)=/', $line, $k) && isset($defined[$k[1]])) {
                    continue;
                }
                $missing[] = $line;
            }
            $contents = trim($current) === '' ? implode("\n", $missing) : rtrim($current)."\n\n".implode("\n", $missing);
        }

        if (! preg_match('/^APP_KEY=base64:.+$/m', $contents)) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            $contents = preg_match('/^APP_KEY=.*$/m', $contents)
                ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents, 1)
                : "APP_KEY={$key}\n".$contents;
        }

        // Atomic replace: write a temporary file next to .env, then rename it over .env.
        $tmp = $env.'.tmp-'.getmypid().'-'.bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, rtrim($contents)."\n") !== false) {
            @chmod($tmp, is_file($env) ? (fileperms($env) & 0777) : 0640);
            if (! @rename($tmp, $env)) {
                @unlink($tmp);
            }
        }
    } finally {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
})();
