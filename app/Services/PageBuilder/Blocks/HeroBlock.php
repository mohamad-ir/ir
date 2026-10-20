<?php

namespace App\Services\PageBuilder\Blocks;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class HeroBlock extends PageBlock
{
    public static function key(): string { return 'hero'; }

    public static function label(): string { return 'Hero'; }

    public static function icon(): string { return 'heroicon-o-sparkles'; }

    public static function schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('eyebrow')->maxLength(80)->placeholder('Ophthalmology education'),
                Select::make('layout')->options(['split' => 'Text + image side by side', 'centered' => 'Centred text', 'background' => 'Full background image'])->default('split')->selectablePlaceholder(false),
            ]),
            Textarea::make('heading')->required()->rows(2)->maxLength(160)->helperText('Wrap words in *asterisks* to highlight them in the accent colour.'),
            Textarea::make('subheading')->rows(3)->maxLength(400),
            MediaPicker::make('image_id')->label('Image'),
            TagsInput::make('highlights')->label('Credential chips')->placeholder('e.g. FRCOphth')->helperText('Short badges shown under the text.'),
            static::buttonsRepeater(),
            Toggle::make('show_search')->label('Show search box')->helperText('Lets visitors search cases, articles and videos straight from the hero.'),
        ];
    }

    public static function prepare(array $data): array
    {
        $data['image'] = Media::find($data['image_id'] ?? null);

        return $data;
    }
}
