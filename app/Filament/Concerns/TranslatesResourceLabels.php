<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

use function Filament\Support\get_model_label;

/**
 * Resource names are written in English in code and shown in the dashboard language.
 * The plural is formed in English first (Filament would otherwise add an English "s" to the Persian word).
 */
trait TranslatesResourceLabels
{
    protected static function englishModelLabel(): string
    {
        return static::$modelLabel ?? static::getLabel() ?? get_model_label(static::getModel());
    }

    public static function getModelLabel(): string
    {
        return __(static::englishModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::$pluralModelLabel ?? static::getPluralLabel() ?? Str::plural(static::englishModelLabel()));
    }

    public static function getNavigationLabel(): string|Htmlable
    {
        return __(static::$navigationLabel ?? Str::ucfirst(Str::plural(static::englishModelLabel())));
    }
}
