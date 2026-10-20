<?php

namespace App\Services\PageBuilder\Blocks;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

class GalleryBlock extends PageBlock
{
    public static function key(): string { return 'gallery'; }

    public static function label(): string { return 'Image gallery'; }

    public static function icon(): string { return 'heroicon-o-rectangle-stack'; }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->maxLength(160),
            Repeater::make('images')->schema([
                MediaPicker::make('media_id')->label('Image')->required(),
                TextInput::make('caption'),
            ])->columns(2)->reorderable()->collapsible()->addActionLabel('Add image'),
            Select::make('columns')->options([2 => 2, 3 => 3, 4 => 4])->default(3)->selectablePlaceholder(false),
            ToggleButtons::make('style')->inline()->options(['light' => 'Light', 'dark' => 'Dark viewer (best for OCT)'])->default('light'),
        ];
    }

    public static function prepare(array $data): array
    {
        $media = Media::whereIn('id', collect($data['images'] ?? [])->pluck('media_id')->filter())->get()->keyBy('id');
        $data['items'] = collect($data['images'] ?? [])->map(fn ($i) => [...$i, 'media' => $media->get($i['media_id'] ?? 0)])->filter(fn ($i) => $i['media'])->values()->all();

        return $data;
    }
}
