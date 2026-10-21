<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\HasBuilderPreview;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\ContentCreateRecord;
use Filament\Support\Enums\Width;

class CreatePage extends ContentCreateRecord
{
    use HasBuilderPreview;

    protected static string $resource = PageResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static function previewType(): string { return 'page'; }

    protected static function permissionPrefix(): string { return 'pages'; }
}
