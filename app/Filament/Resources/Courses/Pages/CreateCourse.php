<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Support\ContentCreateRecord;

class CreateCourse extends ContentCreateRecord
{
    protected static string $resource = CourseResource::class;

    protected static function previewType(): string { return 'course'; }

    protected static function permissionPrefix(): string { return 'courses'; }
}
