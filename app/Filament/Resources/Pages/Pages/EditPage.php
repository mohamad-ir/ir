<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\HasBuilderPreview;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\ContentEditRecord;
use App\Models\ReusableBlock;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;

class EditPage extends ContentEditRecord
{
    use HasBuilderPreview;

    protected static string $resource = PageResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static function previewType(): string { return 'page'; }

    protected static function permissionPrefix(): string { return 'pages'; }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveAsReusable')->label('Save sections as reusable block')->icon('heroicon-o-arrow-path-rounded-square')->color('gray')
                ->visible(fn () => auth()->user()->can('reusable_blocks.manage'))
                ->schema([TextInput::make('name')->required()->default(fn () => $this->record->title.' sections')])
                ->action(function (array $data) {
                    ReusableBlock::create(['name' => $data['name'], 'blocks' => array_values($this->record->blocks ?? [])]);
                    Notification::make()->success()->title('Reusable block created')->body('Place it on any page with the “Reusable block” section.')->send();
                }),
            ...parent::getHeaderActions(),
        ];
    }
}
