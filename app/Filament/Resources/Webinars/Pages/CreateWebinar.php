<?php

namespace App\Filament\Resources\Webinars\Pages;

use App\Filament\Resources\Webinars\WebinarResource;
use App\Filament\Support\ContentCreateRecord;

class CreateWebinar extends ContentCreateRecord
{
    protected static string $resource = WebinarResource::class;

    protected static function previewType(): string { return 'webinar'; }

    protected static function permissionPrefix(): string { return 'webinars'; }
}
