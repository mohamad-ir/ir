<?php

namespace App\Support;

use RuntimeException;

/** Updates key=value pairs in the .env file, preserving everything else. */
class EnvWriter
{
    public static function set(array $values, ?string $path = null): void
    {
        $path ??= base_path('.env');
        if (! is_file($path)) {
            @copy(base_path('.env.example'), $path);
        }
        if (! is_writable($path)) {
            throw new RuntimeException('The .env file is not writable. Set its permissions to 644 (owner writable) in the File Manager.');
        }

        $contents = (string) file_get_contents($path);
        foreach ($values as $key => $value) {
            $line = $key.'='.static::format($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $contents = preg_match($pattern, $contents) ? preg_replace_callback($pattern, fn () => $line, $contents) : rtrim($contents)."\n".$line."\n";
        }
        file_put_contents($path, $contents);
    }

    public static function format(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        return preg_match('/[\s#"\'$\\\\=]/', $value) ? '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"' : $value;
    }
}
