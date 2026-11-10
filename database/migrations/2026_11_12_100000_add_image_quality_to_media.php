<?php

use App\Services\Media\ImageMetadata;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Image quality profile per media item (auto / standard / high / lossless), the profile that was
 * actually applied, and the version of the optimisation pipeline that produced the variants.
 * Existing images get version 0, so the scheduler re-optimises them in the background.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $t) {
            $t->string('quality', 12)->nullable()->after('variants');
            $t->string('optimized_profile', 12)->nullable()->after('quality');
            $t->unsignedTinyInteger('variants_version')->default(0)->after('optimized_profile')->index();
        });

        // Profile photos and site icons written by earlier versions on Imagick hosts could still carry
        // EXIF/GPS data: remove it without re-encoding. (Media library images are cleaned when the
        // scheduler re-optimises them.)
        try {
            $disk = Storage::disk('public');
            foreach (['avatars', 'branding'] as $dir) {
                foreach ($disk->exists($dir) ? $disk->allFiles($dir) : [] as $file) {
                    $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                        'webp' => 'image/webp', 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', default => null,
                    };
                    if ($mime) {
                        ImageMetadata::stripFile($disk->path($file), $mime);
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $t) {
            $t->dropIndex(['variants_version']);
            $t->dropColumn(['quality', 'optimized_profile', 'variants_version']);
        });
    }
};
