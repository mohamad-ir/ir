<?php

namespace App\Services\Media;

use App\Models\Media;
use App\Support\Images;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use Throwable;

/**
 * Stores uploads in the public media library: validates type by content,
 * removes EXIF/GPS metadata without touching the pixels (patient privacy),
 * corrects orientation and generates responsive WebP variants.
 */
class MediaService
{
    public const ALLOWED = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
        'pdf' => 'application/pdf', 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
    ];

    public const MAX_IMAGE_PIXELS = 40_000_000;

    public function __construct(protected string $disk = 'public') {}

    public static function acceptedMimeTypes(): array
    {
        return array_values(array_unique(self::ALLOWED));
    }

    public function store(UploadedFile|string $file, array $attributes = []): Media
    {
        [$sourcePath, $originalName] = $this->resolveSource($file);
        $mime = $this->detectMime($sourcePath);
        $extension = $this->extensionFor($mime, $originalName);

        $hash = sha1_file($sourcePath);
        $directory = 'uploads/'.now()->format('Y/m');
        $basename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file';
        $filename = Str::limit($basename, 60, '').'-'.Str::lower(Str::random(6)).'.'.$extension;
        $path = $directory.'/'.$filename;

        Storage::disk($this->disk)->put($path, fopen($sourcePath, 'r'));

        $media = new Media([
            'disk' => $this->disk,
            'path' => $path,
            'filename' => $filename,
            'original_name' => mb_substr($originalName, 0, 250),
            'mime_type' => $mime,
            'extension' => $extension,
            'hash' => $hash,
            'uploaded_by' => auth()->id(),
            'title' => $attributes['title'] ?? Str::headline(pathinfo($originalName, PATHINFO_FILENAME)),
        ]);
        $media->fill(collect($attributes)->except('title')->all());

        $this->processImage($media);
        $media->size = Storage::disk($this->disk)->size($path);
        $media->save();

        return $media;
    }

    /** Swap the file behind an existing media item; every page using it updates automatically. */
    public function replace(Media $media, UploadedFile|string $file): Media
    {
        [$sourcePath, $originalName] = $this->resolveSource($file);
        $mime = $this->detectMime($sourcePath);
        $extension = $this->extensionFor($mime, $originalName);

        $this->deleteFiles($media);

        $path = $media->extension === $extension
            ? $media->path
            : preg_replace('~\.[a-z0-9]+$~i', '.'.$extension, $media->path);

        Storage::disk($media->disk)->put($path, fopen($sourcePath, 'r'));

        $media->fill([
            'path' => $path,
            'filename' => basename($path),
            'original_name' => mb_substr($originalName, 0, 250),
            'mime_type' => $mime,
            'extension' => $extension,
            'hash' => sha1_file($sourcePath),
            'variants' => null,
            'width' => null,
            'height' => null,
        ]);
        $this->processImage($media);
        $media->size = Storage::disk($media->disk)->size($path);
        $media->touch();
        $media->save();

        return $media;
    }

    public function delete(Media $media): void
    {
        $this->deleteFiles($media);
        $media->delete();
    }

    /**
     * Rebuild the optimised versions. New files overwrite the old ones (same names), and files that
     * are no longer needed are removed only afterwards, so a failure never leaves the site without images.
     */
    public function regenerate(Media $media): Media
    {
        $old = $media->variants ?? [];
        $this->processImage($media, newUpload: false);
        $disk = Storage::disk($media->disk);
        if ($stale = array_diff($old, $media->variants ?? [])) {
            $disk->delete(array_values($stale));
        }
        if ($disk->exists($media->path)) {
            $media->size = $disk->size($media->path);
        }
        $media->save();

        return $media;
    }

    protected function deleteFiles(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $disk->delete($media->path);
        foreach ($media->variants ?? [] as $variant) {
            $disk->delete($variant);
        }
    }

    /**
     * Optimise an image:
     *  1. Clean the original: metadata (EXIF/GPS/XMP/IPTC/comments) is removed losslessly, so
     *     the original keeps every pixel; only a rotated phone photo is re-encoded (JPEG q95).
     *  2. Pick a quality profile (see Media::QUALITY): images in OCT cases and questions,
     *     de-identified patient images, grayscale scans (OCT, red-free, IR) and lossless sources
     *     (PNG) get the high profile automatically.
     *  3. Create WebP variants at each responsive width, never upscaling.
     */
    protected function processImage(Media $media, bool $newUpload = true): void
    {
        if (! in_array($media->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return;
        }

        $disk = Storage::disk($media->disk);
        $fullPath = $disk->path($media->path);

        $info = @getimagesize($fullPath);
        if (! $info) {
            throw new InvalidArgumentException('The uploaded file is not a valid image.');
        }
        if ($info[0] * $info[1] > self::MAX_IMAGE_PIXELS) {
            if ($newUpload) {
                $disk->delete($media->path);
            }
            throw new InvalidArgumentException('This image is too large to process ('.$info[0].'×'.$info[1].'). Please resize it below 40 megapixels.');
        }

        $media->width = $info[0];
        $media->height = $info[1];

        $this->raiseMemoryLimit();
        // Only the first frame is needed for the optimised versions (avoids decoding every frame of an animation).
        $manager = Images::manager(decodeAnimation: false);

        // Privacy first: an image whose metadata cannot be removed is never published.
        try {
            $this->cleanOriginal($media, $fullPath, $manager);
        } catch (Throwable $e) {
            if (! ImageMetadata::stripFile($fullPath, $media->mime_type) && $media->mime_type !== 'image/gif') {
                if ($newUpload) {
                    $disk->delete($media->path);
                    throw new InvalidArgumentException('This image could not be processed safely (its private metadata could not be removed). Please export it again as JPEG or PNG and upload it once more.');
                }
                report($e);
            }
        }
        clearstatcache(true, $fullPath);

        try {
            $image = $manager->read($fullPath);
            if ($image->isAnimated()) {
                $image->removeAnimation(0);
            }
            $media->width = $image->width();
            $media->height = $image->height();

            $profile = $this->profileFor($media, $image);
            $base = preg_replace('~\.[a-z0-9]+$~i', '', $media->path);
            $variants = [];
            $previous = 0;
            foreach (Media::VARIANT_WIDTHS as $name => $width) {
                // GIFs keep their animation, so only a still thumbnail is made for listings.
                if ($media->mime_type === 'image/gif' && $name !== 'thumb') {
                    break;
                }
                $target = min($width, $image->width());
                if ($name !== 'thumb' && $target <= $previous * 1.1) {
                    continue; // the source is not meaningfully larger than the previous size
                }
                $copy = clone $image;
                if ($target < $copy->width()) {
                    $this->downscale($copy, $target);
                }
                $variantPath = "{$base}-{$name}.webp";
                file_put_contents($disk->path($variantPath), $this->encodeWebp($copy, Media::QUALITY[$profile][$name]));
                $variants[$name] = $variantPath;
                $previous = $target;
            }
            $media->variants = $variants;
            $media->optimized_profile = $profile;
            $media->variants_version = Media::VARIANTS_VERSION;
        } catch (Throwable $e) {
            report($e);
            $media->variants_version = Media::VARIANTS_VERSION; // not retried automatically; "Regenerate sizes" can retry
            // The previous optimised versions (if any) are kept untouched.
        }
    }

    /**
     * Re-optimise images made by an older version of the pipeline, a few at a time (called by the
     * scheduler every minute, so large libraries are upgraded without timeouts). Returns the count.
     */
    public function upgradeOutdated(int $limit = 10, int $seconds = 25): int
    {
        $started = microtime(true);
        $done = 0;
        Media::query()->where('variants_version', '<', Media::VARIANTS_VERSION)
            ->where('mime_type', 'like', 'image/%')
            ->orderBy('id')->limit($limit)->get()
            ->each(function (Media $media) use (&$done, $started, $seconds) {
                if (microtime(true) - $started > $seconds) {
                    return false;
                }
                // Marked first: if PHP stops on this image (memory or time limit), it is not retried
                // forever and the queue moves on; its previous versions stay in place.
                $media->forceFill(['variants_version' => Media::VARIANTS_VERSION])->saveQuietly();
                try {
                    $this->regenerate($media);
                } catch (Throwable $e) {
                    report($e);
                }
                $done++;
            });

        return $done;
    }

    /** Quality profile for an image: the one chosen in the library, or an automatic choice. */
    public function profileFor(Media $media, ?ImageInterface $image = null): string
    {
        if (isset(Media::QUALITY[$media->quality ?? ''])) {
            return $media->quality;
        }
        if ($media->is_deidentified || $media->mime_type === 'image/png' || $media->isClinical() || ($image && $this->isGrayscale($image))) {
            return 'high';
        }

        return 'standard';
    }

    /** Mostly-grayscale images are typically OCT B-scans, red-free and infrared fundus images. */
    protected function isGrayscale(ImageInterface $image): bool
    {
        $sample = clone $image;
        $sample->resize(32, 32);
        $colour = 0;
        for ($y = 0; $y < 32; $y++) {
            for ($x = 0; $x < 32; $x++) {
                [$r, $g, $b] = $sample->pickColor($x, $y)->toArray();
                if (max($r, $g, $b) - min($r, $g, $b) > 12) {
                    $colour++;
                }
            }
        }

        return $colour <= 1024 * 0.04; // allow small coloured overlays (measurement lines, labels)
    }

    protected function cleanOriginal(Media $media, string $path, ImageManager $manager): void
    {
        if ($media->mime_type === 'image/gif') {
            return;
        }
        // A photo stored sideways with an EXIF orientation is turned upright (pixels rotated, tag
        // removed), which keeps exactly how browsers displayed it.
        if ($media->mime_type === 'image/jpeg' && ImageMetadata::jpegOrientation((string) file_get_contents($path)) !== 1) {
            file_put_contents($path, (string) $manager->read($path)->toJpeg(95));

            return;
        }
        if (ImageMetadata::stripFile($path, $media->mime_type)) {
            return;
        }
        // Unusual file structure: fall back to a high-quality re-encode, which also removes metadata.
        $image = $manager->read($path);
        file_put_contents($path, (string) match ($media->mime_type) {
            'image/png' => $image->toPng(),
            'image/webp' => $image->toWebp(95),
            default => $image->toJpeg(95),
        });
    }

    protected function downscale(ImageInterface $image, int $width): void
    {
        $native = $image->core()->native();
        if ($native instanceof \Imagick) {
            // Lanczos: the reference filter for sharp downscaling without aliasing.
            $height = max(1, (int) round($image->height() * $width / $image->width()));
            $native->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);

            return;
        }
        $image->scaleDown(width: $width);
    }

    /**
     * WebP is always written by libwebp through GD when available: ImageMagick 6 builds commonly
     * ignore the WebP quality setting (every file comes out at its default of 75). Imagick, when
     * installed, still decodes and resizes (Lanczos, CMYK and 16-bit sources) and hands the
     * pixels over losslessly.
     */
    protected function encodeWebp(ImageInterface $image, int $quality): string
    {
        $native = $image->core()->native();
        if ($native instanceof \Imagick) {
            $im = clone $native;
            $this->convertToSrgb($im);
            $im->stripImage();
            if ($im->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
                $im->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            }
            if (self::gdWebp()) {
                $im->setImageFormat('png');
                $im->setOption('png:compression-level', '1');
                $gd = imagecreatefromstring($im->getImageBlob());
                $im->clear();
                if ($gd !== false) {
                    return self::gdToWebp($gd, $quality);
                }
            }
            $im->setImageFormat('webp');
            $im->setCompressionQuality($quality);
            $im->setImageCompressionQuality($quality);
            if ($quality >= 100) {
                $im->setOption('webp:lossless', 'true');
            }

            return $im->getImageBlob();
        }

        return self::gdToWebp($native, $quality);
    }

    /**
     * Wide-gamut photos (Display P3, Adobe RGB) are converted to sRGB before their colour profile is
     * removed, so the optimised versions keep the same colours as the original.
     */
    protected function convertToSrgb(\Imagick $im): void
    {
        $srgb = base_path('vendor/dompdf/dompdf/lib/res/sRGB2014.icc');
        try {
            if (isset($im->getImageProfiles('icc', true)['icc']) && is_file($srgb)) {
                $im->profileImage('icc', (string) file_get_contents($srgb));
            }
        } catch (Throwable) {
            // No colour management available: keep the pixel values as they are.
        }
    }

    protected static function gdWebp(): bool
    {
        return function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
    }

    protected static function gdToWebp(\GdImage $gd, int $quality): string
    {
        if (! imageistruecolor($gd)) {
            imagepalettetotruecolor($gd);
        }
        imagealphablending($gd, false);
        imagesavealpha($gd, true);
        ob_start();
        imagewebp($gd, null, $quality >= 100 && defined('IMG_WEBP_LOSSLESS') ? IMG_WEBP_LOSSLESS : $quality);

        return (string) ob_get_clean();
    }

    protected function raiseMemoryLimit(): void
    {
        $current = ini_get('memory_limit');
        $bytes = $current === '-1' ? PHP_INT_MAX : (int) $current * match (strtoupper(substr((string) $current, -1))) {
            'G' => 1024 ** 3, 'M' => 1024 ** 2, 'K' => 1024, default => 1,
        };
        if ($bytes < 512 * 1024 ** 2) {
            @ini_set('memory_limit', '512M');
        }
    }

    /** @return array{0: string, 1: string} */
    protected function resolveSource(UploadedFile|string $file): array
    {
        if ($file instanceof UploadedFile) {
            return [$file->getRealPath(), $file->getClientOriginalName()];
        }

        if (! is_file($file)) {
            throw new InvalidArgumentException("File not found: {$file}");
        }

        return [$file, basename($file)];
    }

    protected function detectMime(string $path): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';

        if (! in_array($mime, self::ALLOWED, true)) {
            throw new InvalidArgumentException("Files of type {$mime} are not allowed in the media library.");
        }

        return $mime;
    }

    protected function extensionFor(string $mime, string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (isset(self::ALLOWED[$ext]) && self::ALLOWED[$ext] === $mime) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        return array_search($mime, self::ALLOWED, true) ?: 'bin';
    }
}
