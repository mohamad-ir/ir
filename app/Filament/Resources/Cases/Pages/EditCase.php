<?php

namespace App\Filament\Resources\Cases\Pages;

use App\Filament\Resources\Cases\CaseResource;
use App\Filament\Support\ContentEditRecord;

class EditCase extends ContentEditRecord
{
    protected static string $resource = CaseResource::class;

    protected static function previewType(): string { return 'case'; }

    protected static function permissionPrefix(): string { return 'cases'; }
}
