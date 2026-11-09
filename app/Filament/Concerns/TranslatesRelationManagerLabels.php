<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/** Relation tab titles and record names are written in English in code and shown in the dashboard language. */
trait TranslatesRelationManagerLabels
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __(parent::getTitle($ownerRecord, $pageClass));
    }

    protected static function getModelLabel(): ?string
    {
        $label = parent::getModelLabel();

        return $label === null ? null : __($label);
    }

    protected static function getPluralModelLabel(): ?string
    {
        $label = parent::getPluralModelLabel();

        return $label === null ? null : __($label);
    }
}
