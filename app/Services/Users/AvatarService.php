<?php

namespace App\Services\Users;

use App\Models\User;
use App\Support\Images;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Profile photos. Every upload is decoded and re-encoded server-side, so nothing the member
 * sent is ever served as-is: metadata (EXIF/GPS) and any embedded payload are dropped,
 * the photo is turned upright, cropped square and saved as WebP in three sizes under
 * an unguessable name. The previous photo's files are deleted when it is replaced or removed.
 */
class AvatarService
{
    /** Pixel sizes: sm for small lists (comments, header), md for profile cards, lg for the account page. */
    public const SIZES = ['sm' => 96, 'md' => 256, 'lg' => 512];

    public const MAX_KB = 5120;

    public const MIN_PX = 128;

    public const MAX_PIXELS = 40_000_000;

    public const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Laravel validation rules for the uploaded file. */
    public static function rules(): array
    {
        return ['required', 'file', 'max:'.self::MAX_KB, 'mimes:jpg,jpeg,png,webp', 'mimetypes:'.implode(',', self::MIMES),
            'dimensions:min_width='.self::MIN_PX.',min_height='.self::MIN_PX];
    }

    public function store(User $user, UploadedFile $file): User
    {
        $path = $file->getRealPath();
        $mime = $path ? (new \finfo(FILEINFO_MIME_TYPE))->file($path) : null;
        $info = $path ? @getimagesize($path) : false;

        if (! in_array($mime, self::MIMES, true) || ! $info) {
            throw ValidationException::withMessages(['avatar' => 'Please choose a JPG, PNG or WebP image.']);
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['avatar' => 'This image is too large — please use one below 40 megapixels.']);
        }

        try {
            @ini_set('memory_limit', '512M');
            $image = $this->manager()->read($path); // also applies the EXIF orientation
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['avatar' => 'This image could not be read. Please try another photo.']);
        }

        if (min($image->width(), $image->height()) < self::MIN_PX) {
            throw ValidationException::withMessages(['avatar' => 'Please use a photo at least '.self::MIN_PX.' × '.self::MIN_PX.' pixels.']);
        }

        $disk = Storage::disk('public');
        $base = 'avatars/'.$user->id.'/'.Str::lower(Str::random(24));

        foreach (self::SIZES as $px) {
            $copy = clone $image;
            $copy->cover($px, $px); // centre crop to a square (a no-op for photos already cropped in the browser)
            $disk->put("{$base}-{$px}.webp", (string) $copy->toWebp(85));
        }

        $previous = $user->avatar_path;
        $user->forceFill(['avatar_path' => $base])->save();
        $this->deleteFiles($previous);

        return $user;
    }

    public function remove(User $user): void
    {
        $previous = $user->avatar_path;
        if ($previous) {
            $user->forceFill(['avatar_path' => null])->save();
            $this->deleteFiles($previous);
        }
    }

    public static function url(?string $base, string $size = 'md'): ?string
    {
        if (blank($base)) {
            return null;
        }
        // Paths stored before 1.4 (none in a standard installation) point to a single file.
        if (preg_match('~\.(jpe?g|png|webp|gif)$~i', $base)) {
            return Storage::disk('public')->url($base);
        }

        return Storage::disk('public')->url($base.'-'.(self::SIZES[$size] ?? self::SIZES['md']).'.webp');
    }

    public function deleteFiles(?string $base): void
    {
        if (blank($base) || ! str_starts_with($base, 'avatars/')) {
            return;
        }
        $disk = Storage::disk('public');
        $disk->delete(array_map(fn ($px) => "{$base}-{$px}.webp", self::SIZES));
        $dir = dirname($base);
        if ($disk->directoryExists($dir) && $disk->allFiles($dir) === []) {
            $disk->deleteDirectory($dir);
        }
    }

    protected function manager(): ImageManager
    {
        return Images::manager(decodeAnimation: false);
    }
}
