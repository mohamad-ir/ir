<?php

/*
 * First-run helper for shared hosting: if no .env exists yet, create it from
 * .env.example with a freshly generated APP_KEY so the web installer can start.
 */

$base = dirname(__DIR__);
$env = $base.'/.env';

if (! is_file($env) && is_file($base.'/.env.example')) {
    @copy($base.'/.env.example', $env);
}

if (is_file($env) && is_writable($env)) {
    $contents = (string) file_get_contents($env);
    if (! preg_match('/^APP_KEY=base64:.+$/m', $contents)) {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $contents = preg_match('/^APP_KEY=.*$/m', $contents)
            ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents)
            : "APP_KEY={$key}\n".$contents;
        file_put_contents($env, $contents);
    }
}
