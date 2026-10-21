<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Throwable;

/**
 * Stores uploads in the public media library: validates type by content,
 * strips EXIF/GPS metadata (patient privacy), corrects orientation and
 * generates responsive WebP variants.
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

    public function regenerate(Media $media): Media
    {
        foreach ($media->variants ?? [] as $variant) {
            Storage::disk($media->disk)->delete($variant);
        }
        $media->variants = null;
        $this->processImage($media, stripOriginal: false);
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

    protected function processImage(Media $media, bool $stripOriginal = true): void
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
            $disk->delete($media->path);
            throw new InvalidArgumentException('This image is too large to process ('.$info[0].'×'.$info[1].'). Please resize it below 40 megapixels.');
        }

        try {
            @ini_set('memory_limit', '512M');
            $manager = $this->manager();
            $image = $manager->read($fullPath);

            // Re-encoding drops all EXIF/IPTC/XMP metadata (GPS, device, patient data in comments).
            if ($stripOriginal && $media->mime_type !== 'image/gif') {
                $encoded = match ($media->mime_type) {
                    'image/png' => $image->toPng(),
                    'image/webp' => $image->toWebp(90),
                    default => $image->toJpeg(90),
                };
                file_put_contents($fullPath, (string) $encoded);
            }

            $media->width = $image->width();
            $media->height = $image->height();

            $variants = [];
            foreach (Media::VARIANT_WIDTHS as $name => $width) {
                if ($name !== 'thumb' && $image->width() <= $width * 0.8 && $variants) {
                    continue;
                }
                $variantPath = preg_replace('~\.[a-z0-9]+$~i', '', $media->path)."-{$name}.webp";
                $copy = $manager->read($fullPath);
                $copy->scaleDown(width: $width);
                file_put_contents($disk->path($variantPath), (string) $copy->toWebp($name === 'thumb' ? 75 : 82));
                $variants[$name] = $variantPath;
            }
            $media->variants = $variants;
        } catch (Throwable $e) {
            report($e);
            $media->width = $info[0];
            $media->height = $info[1];
        }
    }

    protected function manager(): ImageManager
    {
        return extension_loaded('imagick') && class_exists(\Imagick::class)
            ? new ImageManager(new ImagickDriver)
            : new ImageManager(new GdDriver);
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
