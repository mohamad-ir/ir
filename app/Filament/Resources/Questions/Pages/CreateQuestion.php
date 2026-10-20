<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Support\ContentCreateRecord;

class CreateQuestion extends ContentCreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected static function previewType(): string { return 'question'; }

    protected static function permissionPrefix(): string { return 'questions'; }
}
