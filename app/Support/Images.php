<?php

namespace App\Support;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * One place to create the image library, so every upload path (media library, profile photos,
 * branding) uses the same driver and always removes metadata (EXIF/GPS/XMP) from what it writes.
 */
class Images
{
    public static function usesImagick(): bool
    {
        $driver = config('cms.image_driver', 'auto');

        return $driver !== 'gd' && extension_loaded('imagick') && class_exists(\Imagick::class);
    }

    public static function manager(bool $decodeAnimation = true): ImageManager
    {
        // strip: Imagick otherwise copies EXIF (GPS, device, comments) into every encoded file.
        return self::usesImagick()
            ? new ImageManager(new ImagickDriver, decodeAnimation: $decodeAnimation, strip: true)
            : new ImageManager(new GdDriver, decodeAnimation: $decodeAnimation, strip: true);
    }
}
