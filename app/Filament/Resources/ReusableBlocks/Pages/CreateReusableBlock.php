<?php

namespace App\Filament\Resources\ReusableBlocks\Pages;

use App\Filament\Resources\ReusableBlocks\ReusableBlockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReusableBlock extends CreateRecord
{
    use \App\Filament\Concerns\HasBuilderPreview;

    protected \Filament\Support\Enums\Width|string|null $maxContentWidth = \Filament\Support\Enums\Width::Full;

    protected static string $resource = ReusableBlockResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
