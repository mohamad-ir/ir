<?php

namespace App\Services\PageBuilder\Blocks;

use App\Models\Banner;
use App\Services\PageBuilder\PageBlock;
use Filament\Forms\Components\Select;

class BannerSliderBlock extends PageBlock
{
    public static function key(): string { return 'banner_slider'; }

    public static function label(): string { return 'Banner slider'; }

    public static function icon(): string { return 'heroicon-o-rectangle-group'; }

    public static function description(): string { return 'Show one of your image banners / sliders (Design › Banners).'; }

    public static function schema(): array
    {
        return [
            Select::make('banner_id')->label('Banner')->required()->searchable()
                ->options(fn () => Banner::orderBy('name')->pluck('name', 'id'))
                ->helperText('Create and edit banners under Design › Banners & sliders.'),
        ];
    }

    public static function defaultTone(): string
    {
        return 'default';
    }

    public static function shouldRender(array $prepared, array $context): bool
    {
        return ($context['preview'] ?? false) || $prepared['banner'] !== null;
    }

    public static function prepare(array $data): array
    {
        $banner = Banner::where('is_active', true)->with(['slides.media', 'slides.mobileMedia'])->find($data['banner_id'] ?? 0);
        $data['banner'] = $banner && $banner->liveSlides()->isNotEmpty() ? $banner : null;

        return $data;
    }
}
