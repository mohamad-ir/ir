<?php

use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\ClinicalCase;
use App\Models\Gallery;
use App\Models\Media;
use App\Models\Question;
use App\Models\User;
use App\Services\Media\ImageMetadata;
use App\Services\Media\MediaService;
use App\Services\Users\AvatarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Livewire\Livewire;

/*
 * Image pipeline: originals keep every pixel, metadata is removed, variants are sharp enough for
 * large and high-density screens, and clinical images get a higher quality profile.
 */

function drivers(): array
{
    return extension_loaded('imagick') ? ['gd', 'imagick'] : ['gd'];
}

/** A detailed test image (gradient, fine lines and noise) written to a temporary file. */
function testImage(int $w, int $h, string $type = 'jpg', bool $gray = false): string
{
    $im = imagecreatetruecolor($w, $h);
    mt_srand(7);
    for ($y = 0; $y < $h; $y += 2) {
        for ($x = 0; $x < $w; $x += 2) {
            $v = (int) (($x / $w) * 160 + mt_rand(0, 60));
            $c = $gray ? imagecolorallocate($im, $v, $v, $v) : imagecolorallocate($im, min(255, $v + 60), (int) ($v / 2), 40);
            imagefilledrectangle($im, $x, $y, $x + 1, $y + 1, $c);
        }
    }
    for ($i = 0; $i < 40; $i++) {
        imageline($im, 0, $i * 13, $w, $i * 17, imagecolorallocate($im, $gray ? 250 : 120, $gray ? 250 : 20, $gray ? 250 : 20));
    }
    $path = sys_get_temp_dir().'/oa-img-'.uniqid().'.'.$type;
    match ($type) {
        'png' => imagepng($im, $path),
        'webp' => imagewebp($im, $path, 90),
        'gif' => imagegif($im, $path),
        default => imagejpeg($im, $path, 92),
    };

    return $path;
}

/** Adds an EXIF block (camera make, orientation), a comment and an IPTC block to a JPEG. */
function withExif(string $jpeg, int $orientation = 1): string
{
    $make = "SecretCam\0";
    $tiff = 'MM'."\x00\x2A".pack('N', 8).pack('n', 2)
        .pack('nnN', 0x0112, 3, 1).pack('n', $orientation)."\0\0"
        .pack('nnNN', 0x010F, 2, strlen($make), 38)
        .pack('N', 0).$make;
    $app1 = "Exif\0\0".$tiff;
    $com = 'Patient John Doe, GPS 35.6892N 51.3890E';
    $app13 = "Photoshop 3.0\0"."8BIM\x04\x04\0\0\0\0\0\x0cIPTC-SECRET";
    $bytes = file_get_contents($jpeg);
    $bytes = "\xFF\xD8"
        ."\xFF\xE1".pack('n', strlen($app1) + 2).$app1
        ."\xFF\xFE".pack('n', strlen($com) + 2).$com
        ."\xFF\xED".pack('n', strlen($app13) + 2).$app13
        .substr($bytes, 2);
    file_put_contents($jpeg, $bytes);

    return $jpeg;
}

function scanData(string $jpeg): string
{
    return substr($jpeg, strpos($jpeg, "\xFF\xDA"));
}

beforeEach(fn () => Storage::fake('public'));

it('removes EXIF, GPS, comments and IPTC from originals and every variant without re-encoding the pixels', function (string $driver) {
    config(['cms.image_driver' => $driver]);
    $source = withExif(testImage(1200, 800));
    $before = file_get_contents($source);

    $media = app(MediaService::class)->store($source);
    $original = Storage::disk('public')->get($media->path);

    expect($original)->not->toContain('SecretCam')->not->toContain('John Doe')->not->toContain('IPTC-SECRET')
        ->and(scanData($original))->toBe(scanData($before))            // compressed image data identical: no quality loss
        ->and(getimagesize(Storage::disk('public')->path($media->path))[0])->toBe(1200);
    foreach ($media->variants as $variant) {
        expect(Storage::disk('public')->get($variant))->not->toContain('SecretCam')->not->toContain('John Doe');
    }
})->with(drivers());

it('turns sideways phone photos upright once, at upload', function (string $driver) {
    config(['cms.image_driver' => $driver]);
    $media = app(MediaService::class)->store(withExif(testImage(1200, 800), orientation: 6));

    expect([$media->width, $media->height])->toBe([800, 1200])
        ->and(Storage::disk('public')->get($media->path))->not->toContain('SecretCam')
        ->and(getimagesize(Storage::disk('public')->path($media->variants['md']))[1])->toBeGreaterThan(getimagesize(Storage::disk('public')->path($media->variants['md']))[0]);
})->with(drivers());

it('strips text chunks from PNG and EXIF/XMP from WebP but keeps colour profiles', function () {
    $png = file_get_contents(testImage(64, 64, 'png'));
    $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    $withMeta = substr($png, 0, 33).$chunk('tEXt', "Comment\0Patient John Doe").$chunk('iCCP', "sRGB\0\0".gzcompress('x')).$chunk('eXIf', 'MM-SecretCam').substr($png, 33);

    $clean = ImageMetadata::stripPng($withMeta);
    expect($clean)->not->toContain('John Doe')->not->toContain('SecretCam')->toContain('iCCP')
        ->and(imagecreatefromstring($clean))->not->toBeFalse();

    $webp = file_get_contents(testImage(64, 64, 'webp'));
    $payload = substr($webp, 12);
    $vp8x = 'VP8X'.pack('V', 10).chr(0x08 | 0x04).str_repeat("\0", 3).substr(pack('V', 63), 0, 3).substr(pack('V', 63), 0, 3);
    $exif = 'EXIF'.pack('V', 13).'MM-SecretCam!'."\0";
    $body = 'WEBP'.$vp8x.$payload.$exif;
    $clean = ImageMetadata::stripWebp('RIFF'.pack('V', strlen($body)).$body);

    expect($clean)->not->toContain('SecretCam')
        ->and(ord($clean[20]) & 0x0C)->toBe(0)                           // EXIF/XMP flags cleared
        ->and(unpack('V', substr($clean, 4, 4))[1])->toBe(strlen($clean) - 8);
});

it('re-encodes files it cannot parse instead of keeping their metadata', function () {
    expect(ImageMetadata::stripJpeg("\xFF\xD8\xFF\xE1\x00"))->toBeNull()
        ->and(ImageMetadata::stripPng('not a png'))->toBeNull()
        ->and(ImageMetadata::stripWebp('RIFF....WEBP'))->toBeNull();
});

it('creates sizes for large and high-density screens without ever upscaling', function (string $driver, int $width, array $expected) {
    config(['cms.image_driver' => $driver]);
    $media = app(MediaService::class)->store(testImage($width, (int) ($width / 2)));
    $widths = collect($media->variants)->map(fn ($v) => getimagesize(Storage::disk('public')->path($v))[0])->all();

    expect($widths)->toBe($expected)
        ->and($media->variants_version)->toBe(Media::VARIANTS_VERSION);
})->with(drivers())->with([
    'very large photo' => [3200, ['thumb' => 400, 'md' => 960, 'lg' => 1600, 'xl' => 2560]],
    'between sizes' => [1900, ['thumb' => 400, 'md' => 960, 'lg' => 1600, 'xl' => 1900]],
    'close to a size' => [1650, ['thumb' => 400, 'md' => 960, 'lg' => 1600]],
    'small image' => [1100, ['thumb' => 400, 'md' => 960, 'lg' => 1100]],
    'tiny image' => [300, ['thumb' => 300]],
]);

it('lists every size in srcset so large screens never get a stretched image', function () {
    $media = app(MediaService::class)->store(testImage(3200, 1600));

    expect($media->srcset())->toContain(' 400w')->toContain(' 960w')->toContain(' 1600w')->toContain(' 2560w')
        ->and($media->largestUrl())->toContain('-xl.webp');
});

it('chooses the high profile for grayscale scans, PNG files and de-identified patient images', function () {
    $service = app(MediaService::class);

    expect($service->store(testImage(1000, 500))->optimized_profile)->toBe('standard')
        ->and($service->store(testImage(1000, 500, 'jpg', gray: true))->optimized_profile)->toBe('high')
        ->and($service->store(testImage(1000, 500, 'png'))->optimized_profile)->toBe('high')
        ->and($service->store(testImage(1000, 500), ['is_deidentified' => true])->optimized_profile)->toBe('high');
});

it('really encodes the profiles at different qualities, and lossless is pixel exact', function (string $driver) {
    config(['cms.image_driver' => $driver]);
    $service = app(MediaService::class);
    $source = testImage(1200, 600);
    $sizes = [];
    foreach (['standard', 'high', 'lossless'] as $profile) {
        $media = $service->store($source, ['quality' => $profile]);
        expect($media->optimized_profile)->toBe($profile);
        $sizes[$profile] = Storage::disk('public')->size($media->variants['lg']);
        $lg = Storage::disk('public')->get($media->variants['lg']);
    }

    expect($sizes['high'])->toBeGreaterThan((int) ($sizes['standard'] * 1.1))
        ->and($sizes['lossless'])->toBeGreaterThan($sizes['high'])
        ->and(substr($lg, 12, 4))->toBe('VP8L');                            // the lossless WebP bitstream
})->with(drivers());

it('re-optimises images used in OCT cases and questions with the clinical profile', function () {
    $service = app(MediaService::class);
    $fundus = $service->store(testImage(1400, 1000));
    $question = $service->store(testImage(1400, 1000));
    expect($fundus->optimized_profile)->toBe('standard');

    ClinicalCase::factory()->create(['images' => [['media_id' => $fundus->id, 'caption' => 'Fundus']]]);
    Question::factory()->create(['media_id' => $question->id]);
    expect($fundus->fresh()->variants_version)->toBe(0)->and($question->fresh()->variants_version)->toBe(0);

    expect($service->upgradeOutdated())->toBe(2)
        ->and($fundus->fresh()->optimized_profile)->toBe('high')
        ->and($question->fresh()->optimized_profile)->toBe('high')
        ->and($fundus->fresh()->variants_version)->toBe(Media::VARIANTS_VERSION);
});

it('upgrades images made by earlier versions in the background and from the command line', function () {
    $media = app(MediaService::class)->store(testImage(2000, 1000));
    $media->forceFill(['variants' => ['thumb' => $media->variants['thumb']], 'variants_version' => 0, 'optimized_profile' => null])->save();
    $original = Storage::disk('public')->get($media->path);

    expect(app(MediaService::class)->upgradeOutdated())->toBe(1)
        ->and($media->fresh()->variants)->toHaveKeys(['thumb', 'md', 'lg', 'xl'])
        ->and(Storage::disk('public')->get($media->path))->toBe($original);  // originals are never re-encoded

    $media->forceFill(['variants_version' => 1])->save();
    expect(Artisan::call('media:optimize'))->toBe(0)->and($media->fresh()->variants_version)->toBe(Media::VARIANTS_VERSION)
        ->and(Artisan::call('media:optimize', ['--id' => [$media->id]]))->toBe(0);
});

it('keeps GIF animations by always serving the original', function () {
    $media = app(MediaService::class)->store(testImage(900, 600, 'gif'));

    expect(array_keys($media->variants))->toBe(['thumb'])
        ->and($media->srcset())->toBeNull()
        ->and($media->url('md'))->toBe($media->url());
});

it('removes metadata from profile photos too', function () {
    config(['cms.image_driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
    $user = User::factory()->create();
    $file = new UploadedFile(withExif(testImage(600, 600)), 'me.jpg', 'image/jpeg', null, true);

    app(AvatarService::class)->store($user, $file);
    foreach (Storage::disk('public')->allFiles() as $path) {
        expect(Storage::disk('public')->get($path))->not->toContain('SecretCam');
    }
});

it('shows the original when zooming in the OCT case viewer and the gallery viewer', function () {
    $media = app(MediaService::class)->store(testImage(3000, 1500));
    $case = ClinicalCase::factory()->create(['images' => [['media_id' => $media->id]]]);

    $this->get($case->url())->assertOk()
        ->assertSee('hd ? current.full : current.src', false)
        ->assertSee(basename($media->variants['xl']), false);

    $gallery = Gallery::factory()->create();
    $gallery->items()->create(['media_id' => $media->id, 'sort_order' => 1]);
    $this->get($gallery->url())->assertOk()->assertSee('hd ? slide.full : slide.src', false)->assertSee('toggleZoom()', false);
});

it('lets editors choose the quality of an image, which rebuilds its sizes', function () {
    $this->seedRoles();
    $this->actingAsStaff();
    $media = app(MediaService::class)->store(testImage(1400, 700));

    $this->get('/admin/media/'.$media->id.'/edit')->assertOk()->assertSee('Image quality')->assertSee('lg · 1400px', false);

    Livewire::test(EditMedia::class, ['record' => $media->id])
        ->fillForm(['quality' => 'lossless'])->call('save')->assertHasNoFormErrors();
    expect($media->fresh()->quality)->toBe('lossless')->and($media->fresh()->optimized_profile)->toBe('lossless');

    Livewire::test(ListMedia::class)
        ->callTableBulkAction('quality', [$media->id], ['quality' => 'auto']);
    expect($media->fresh()->quality)->toBeNull()->and($media->fresh()->variants_version)->toBe(0);
});

it('never deletes the current versions before new ones exist, and a failing image does not block the queue', function () {
    $service = app(MediaService::class);
    $broken = $service->store(testImage(1200, 600));
    $good = $service->store(testImage(1200, 600));
    $brokenVariants = $broken->variants;
    // An outdated item whose original has become unreadable…
    Storage::disk('public')->put($broken->path, 'not an image any more');
    Media::whereKey([$broken->id, $good->id])->update(['variants_version' => 0]);
    // …and a stale file from an older size list on the good one.
    Storage::disk('public')->put('uploads/stale-old.webp', 'x');
    $good->forceFill(['variants' => [...$good->variants, 'old' => 'uploads/stale-old.webp']])->saveQuietly();

    expect($service->upgradeOutdated())->toBe(2)
        ->and($broken->fresh()->variants_version)->toBe(Media::VARIANTS_VERSION)        // not retried forever
        ->and($broken->fresh()->variants)->toBe($brokenVariants)
        ->and($good->fresh()->variants_version)->toBe(Media::VARIANTS_VERSION);
    foreach ($brokenVariants as $file) {
        Storage::disk('public')->assertExists($file);                                   // still served
    }
    Storage::disk('public')->assertMissing('uploads/stale-old.webp');
});

it('refuses an upload whose private metadata cannot be removed', function () {
    $service = new class extends MediaService
    {
        protected function cleanOriginal(Media $media, string $path, ImageManager $manager): void
        {
            throw new RuntimeException('decoder failed');
        }
    };
    // When re-encoding fails, the lossless clean-up still removes the metadata…
    $media = $service->store(withExif(testImage(800, 600), orientation: 6));
    expect(Storage::disk('public')->get($media->path))->not->toContain('SecretCam');

    // …and a file that cannot be cleaned at all is rejected and deleted.
    $truncated = withExif(testImage(800, 600));
    file_put_contents($truncated, substr(file_get_contents($truncated), 0, -2)); // no end-of-image marker
    expect(fn () => $service->store($truncated))->toThrow(InvalidArgumentException::class);
    expect(collect(Storage::disk('public')->allFiles())->filter(fn ($f) => ! str_contains($f, $media->filename) && ! str_contains($f, pathinfo($media->path, PATHINFO_FILENAME))))->toBeEmpty();
});

it('turns an original that still carries an orientation tag upright when it is re-optimised', function () {
    $media = app(MediaService::class)->store(testImage(1200, 800));
    Storage::disk('public')->put($media->path, file_get_contents(withExif(testImage(1200, 800), orientation: 6)));

    app(MediaService::class)->regenerate($media);
    expect([$media->width, $media->height])->toBe([800, 1200])
        ->and(Storage::disk('public')->get($media->path))->not->toContain('SecretCam');
});

it('recognises clinical images whatever way the case stores their id', function () {
    $media = app(MediaService::class)->store(testImage(900, 600));
    $other = app(MediaService::class)->store(testImage(900, 600));
    ClinicalCase::factory()->create(['images' => [['media_id' => (string) $media->id]], 'comparison' => ['before_media_id' => $other->id, 'after_media_id' => '999999']]);

    expect($media->isClinical())->toBeTrue()->and($other->isClinical())->toBeTrue()
        ->and(app(MediaService::class)->store(testImage(900, 600))->isClinical())->toBeFalse();
});
