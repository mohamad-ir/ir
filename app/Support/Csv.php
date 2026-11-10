<?php

namespace App\Support;

/** CSV rows safe to open in Excel or Google Sheets (no formulas from names, titles or emails). */
class Csv
{
    /** @param  resource  $handle */
    public static function put($handle, array $row): void
    {
        fputcsv($handle, array_map([self::class, 'cell'], $row));
    }

    public static function cell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($value)) {
            return "'".$value;
        }

        return $value;
    }
}
