<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/** Page titles and menu labels are written in English in code and shown in the dashboard language. */
trait TranslatesPageLabels
{
    public static function getNavigationLabel(): string|Htmlable
    {
        $label = parent::getNavigationLabel();

        return is_string($label) ? __($label) : $label;
    }

    public function getTitle(): string|Htmlable
    {
        $title = parent::getTitle();

        return is_string($title) ? __($title) : $title;
    }
}
