<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\ContentCreateRecord;

class CreateArticle extends ContentCreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected static function previewType(): string { return 'article'; }

    protected static function permissionPrefix(): string { return 'articles'; }
}
