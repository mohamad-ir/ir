<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Support\ContentEditRecord;

class EditCourse extends ContentEditRecord
{
    protected static string $resource = CourseResource::class;

    protected static function previewType(): string { return 'course'; }

    protected static function permissionPrefix(): string { return 'courses'; }

    public function getContentTabLabel(): ?string
    {
        return 'Course details';
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
