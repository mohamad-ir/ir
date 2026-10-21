<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideoResource;
use App\Filament\Support\ContentCreateRecord;

class CreateVideo extends ContentCreateRecord
{
    protected static string $resource = VideoResource::class;

    protected static function previewType(): string { return 'video'; }

    protected static function permissionPrefix(): string { return 'videos'; }
}
