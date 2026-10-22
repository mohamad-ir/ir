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
            ToggleButtons::make('source')->label('Images')->inline()->default('images')->live()
                ->options(['images' => 'Choose images here', 'album' => 'Show a photo gallery']),
            Select::make('gallery_id')->label('Photo gallery')->searchable()
                ->options(fn () => \App\Models\Gallery::published()->orderBy('title')->pluck('title', 'id'))
                ->visible(fn ($get) => $get('source') === 'album')->required(fn ($get) => $get('source') === 'album'),
            TextInput::make('limit')->label('Show at most')->numeric()->default(12)->minValue(1)->maxValue(60)
                ->visible(fn ($get) => $get('source') === 'album'),
            Repeater::make('images')->visible(fn ($get) => $get('source') !== 'album')->schema([
                MediaPicker::make('media_id')->label('Image')->required(),
                TextInput::make('caption'),
            ])->columns(2)->reorderable()->collapsible()->addActionLabel('Add image'),
            Select::make('layout')->label('Layout')->options(\App\Models\Gallery::LAYOUTS)->default('grid')->selectablePlaceholder(false)
                ->visible(fn ($get) => $get('source') !== 'album'),
            ToggleButtons::make('style')->inline()->options(['light' => 'Light', 'dark' => 'Dark viewer (best for OCT)'])->default('light'),
        ];
    }

    public static function prepare(array $data): array
    {
        if (($data['source'] ?? 'images') === 'album') {
            $album = \App\Models\Gallery::published()->with('items.media')->find($data['gallery_id'] ?? 0);
            $data['album'] = $album && app(\App\Services\Access\ContentAccess::class)->canView($album, auth()->user()) ? $album : null;
            $data['album_items'] = $data['album'] ? $album->items->filter(fn ($i) => $i->media)->take((int) ($data['limit'] ?? 12))->values() : collect();

            return $data;
        }

        $media = Media::whereIn('id', collect($data['images'] ?? [])->pluck('media_id')->filter())->get()->keyBy('id');
        $data['items'] = collect($data['images'] ?? [])->map(fn ($i) => [...$i, 'media' => $media->get($i['media_id'] ?? 0)])->filter(fn ($i) => $i['media'])->values()->all();

        return $data;
    }
}
