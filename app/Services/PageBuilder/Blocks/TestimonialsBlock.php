<?php

namespace App\Services\PageBuilder\Blocks;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class TestimonialsBlock extends PageBlock
{
    public static function key(): string { return 'testimonials'; }

    public static function label(): string { return 'Testimonials'; }

    public static function icon(): string { return 'heroicon-o-chat-bubble-bottom-center-text'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->maxLength(80),
            TextInput::make('heading')->maxLength(160),
            Repeater::make('items')->schema([
                Textarea::make('quote')->required()->rows(3)->columnSpanFull(),
                TextInput::make('name')->required(),
                TextInput::make('role')->placeholder('ST5 Ophthalmology, London'),
                MediaPicker::make('photo_id')->label('Photo (optional)')->columnSpanFull(),
            ])->columns(2)->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['name'] ?? null)->addActionLabel('Add testimonial'),
        ];
    }

    public static function prepare(array $data): array
    {
        $ids = collect($data['items'] ?? [])->pluck('photo_id')->filter();
        $media = Media::whereIn('id', $ids)->get()->keyBy('id');
        $data['items'] = collect($data['items'] ?? [])->map(fn ($i) => [...$i, 'photo' => $media->get($i['photo_id'] ?? 0)])->all();

        return $data;
    }
}
