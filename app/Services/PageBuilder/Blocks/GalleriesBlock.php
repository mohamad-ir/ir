<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Gallery;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class GalleriesBlock extends PageBlock
{
    public static function key(): string { return 'galleries'; }

    public static function label(): string { return 'Photo galleries'; }

    public static function icon(): string { return 'heroicon-o-photo'; }

    public static function description(): string { return 'Cards linking to your photo galleries (featured first).'; }

    public static function schema(): array
    {
        return [
            TextInput::make('eyebrow')->default('Image galleries')->maxLength(80),
            TextInput::make('heading')->default('Browse the image collections')->maxLength(160),
            Textarea::make('intro')->rows(2),
            TextInput::make('limit')->numeric()->default(3)->minValue(1)->maxValue(12),
        ];
    }

    public static function shouldRender(array $prepared, array $context): bool
    {
        return ($context['preview'] ?? false) || collect($prepared['galleries'] ?? [])->isNotEmpty();
    }

    public static function prepare(array $data): array
    {
        $data['galleries'] = Gallery::published()->with(['coverMedia', 'items.media', 'topics'])->withCount('items')
            ->orderByDesc('is_featured')->orderBy('sort_order')->latest('published_at')->limit((int) ($data['limit'] ?? 3))->get();

        return $data;
    }
}
