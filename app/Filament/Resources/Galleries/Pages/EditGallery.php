<?php

namespace App\Filament\Resources\Galleries\Pages;

use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Support\ContentEditRecord;

class EditGallery extends ContentEditRecord
{
    protected static string $resource = GalleryResource::class;

    protected static function previewType(): string { return 'gallery'; }

    protected static function permissionPrefix(): string { return 'galleries'; }

    public function getContentTabLabel(): ?string
    {
        return 'Gallery details';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
