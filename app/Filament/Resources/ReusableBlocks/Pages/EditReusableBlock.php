<?php

namespace App\Filament\Resources\ReusableBlocks\Pages;

use App\Filament\Resources\ReusableBlocks\ReusableBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReusableBlock extends EditRecord
{
    use \App\Filament\Concerns\HasBuilderPreview;

    protected \Filament\Support\Enums\Width|string|null $maxContentWidth = \Filament\Support\Enums\Width::Full;

    protected static string $resource = ReusableBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

}
