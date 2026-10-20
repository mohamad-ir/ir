<?php

namespace App\RichEditor;

use App\Filament\Forms\MediaPicker;
use App\Models\Media;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

/** Responsive figure from the media library with caption and credit. */
class FigureBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'figure';
    }

    public static function getLabel(): string
    {
        return 'Figure from media library';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Insert an optimised, responsive image with a numbered caption.')
            ->schema([
                MediaPicker::make('media_id')->label('Image')->required(),
                Textarea::make('caption')->rows(2)->helperText('Leave empty to use the caption saved in the media library.'),
                Select::make('size')->options(['normal' => 'Text width', 'wide' => 'Wide', 'full' => 'Full width'])->default('normal'),
            ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        $media = Media::find($config['media_id'] ?? null);

        return 'Figure: '.($media?->title ?: $media?->original_name ?: 'missing image');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $media = Media::find($config['media_id'] ?? null);

        return $media ? '<img src="'.e($media->thumbUrl()).'" style="max-height:12rem;border-radius:.5rem" alt="">' : null;
    }

    public static function toHtml(array $config, array $data): string
    {
        $media = Media::find($config['media_id'] ?? null);
        if (! $media) {
            return '';
        }

        $size = in_array($config['size'] ?? 'normal', ['normal', 'wide', 'full']) ? $config['size'] : 'normal';
        $caption = trim($config['caption'] ?? '') ?: $media->caption;
        $credit = $media->credit ? ' <span class="figure-credit">'.e($media->credit).'</span>' : '';
        $srcset = $media->srcset() ? ' srcset="'.e($media->srcset()).'" sizes="(min-width: 1024px) 860px, 100vw"' : '';
        $dims = $media->width ? " width=\"{$media->width}\" height=\"{$media->height}\"" : '';

        return '<figure class="figure figure-'.$size.'"><img src="'.e($media->url('lg')).'"'.$srcset.$dims.' alt="'.e($media->altText()).'">'
            .(($caption || $credit) ? '<figcaption>'.e($caption).$credit.'</figcaption>' : '').'</figure>';
    }
}
