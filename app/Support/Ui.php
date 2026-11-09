<?php

namespace App\Support;

/** Dashboard wording helpers. Values passed here are interface words, never stored data. */
class Ui
{
    /** Translates the labels of an options list for the dashboard language; keys are untouched. */
    public static function options(array $options): array
    {
        return array_map(fn ($label) => is_string($label) ? __($label) : $label, $options);
    }
}
