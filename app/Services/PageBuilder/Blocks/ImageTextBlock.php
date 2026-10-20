<?php

namespace App\Services\PageBuilder\Blocks;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use App\Services\PageBuilder\PageBlock;
use App\Support\Html;
use App\Support\RichText;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;

class ImageTextBlock extends PageBlock
{
    public static function key(): string { return 'image_text'; }

    public static function label(): string { return 'Image & text'; }

    public static function icon(): string { return 'heroicon-o-photo'; }

    public static function schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('eyebrow')->maxLength(80),
                TextInput::make('heading')->maxLength(160),
            ]),
            RichEditor::make('content')->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['h3']]),
            MediaPicker::make('image_id')->label('Image')->required(),
            Grid::make(2)->schema([
                ToggleButtons::make('image_position')->inline()->options(['left' => 'Image left', 'right' => 'Image right'])->default('right'),
                ToggleButtons::make('image_style')->inline()->options(['rounded' => 'Rounded', 'framed' => 'Framed', 'dark' => 'Dark viewer'])->default('rounded'),
            ]),
            static::buttonsRepeater(),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['image'] = Media::find($data['image_id'] ?? null);
        $data['html'] = RichText::render($data['content'] ?? '');

        return $data;
    }

    public static function plainText(array $data): string
    {
        return trim(($data['heading'] ?? '').' '.Html::text(is_string($data['content'] ?? null) ? $data['content'] : ''));
    }
}
