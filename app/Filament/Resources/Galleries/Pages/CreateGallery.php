<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Support\ContentCreateRecord;

class CreateGallery extends ContentCreateRecord
{
    protected static string $resource = GalleryResource::class;

    protected static function previewType(): string { return 'gallery'; }

    protected static function permissionPrefix(): string { return 'galleries'; }
}
