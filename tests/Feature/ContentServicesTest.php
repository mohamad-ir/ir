<?php

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Services\Media\MediaService;
use App\Services\Theme\ThemeService;
use App\Support\RichText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('strips metadata, records dimensions and creates responsive WebP variants', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('scan.jpg', 1800, 900);

    $media = app(MediaService::class)->store($file, ['alt' => 'OCT scan']);

    expect($media->width)->toBe(1800)->and($media->variants)->toHaveKeys(['thumb', 'md', 'lg']);
    Storage::disk('public')->assertExists($media->variants['md']);
    expect(getimagesize(Storage::disk('public')->path($media->variants['thumb']))[0])->toBe(400);
});

it('rejects files whose content is not an allowed type', function () {
    Storage::fake('public');
    $fake = UploadedFile::fake()->createWithContent('innocent.jpg', '<?php echo "pwned";');

    app(MediaService::class)->store($fake);
})->throws(InvalidArgumentException::class);

it('replaces a file while keeping the media record', function () {
    Storage::fake('public');
    $service = app(MediaService::class);
    $media = $service->store(UploadedFile::fake()->image('a.jpg', 800, 600));
    $id = $media->id;

    $service->replace($media, UploadedFile::fake()->image('b.jpg', 1200, 500));

    expect($media->fresh()->id)->toBe($id)->and($media->fresh()->width)->toBe(1200);
});

it('keeps revisions and restores an earlier version', function () {
    $this->actingAs($this->staff());
    $case = ClinicalCase::factory()->create(['diagnosis' => 'First diagnosis']);
    $case->update(['diagnosis' => 'Second diagnosis']);

    expect($case->revisions()->count())->toBe(2);
    $first = $case->revisions()->get()->last();
    $case->restoreRevision($first);

    expect($case->fresh()->diagnosis)->toBe('First diagnosis')->and($case->revisions()->count())->toBe(3);
});

it('sanitises rich text and links citations', function () {
    $out = RichText::process('<h2>Intro</h2><p>Text [1, 2] <script>alert(1)</script><a href="javascript:alert(1)">x</a></p><table><tr><td>a</td></tr></table>', [['citation' => 'A'], ['citation' => 'B']]);

    expect($out['html'])->not->toContain('<script')->not->toContain('javascript:')
        ->toContain('href="#ref-1"')->toContain('table-wrap')
        ->and($out['toc'][0])->toMatchArray(['id' => 'intro', 'text' => 'Intro']);
});

it('expands callout custom blocks', function () {
    $html = '<div data-type="customBlock" data-id="callout" data-config="'.e(json_encode(['tone' => 'pearl', 'title' => '', 'text' => 'Ask about steroids'])).'"></div>';

    expect((string) RichText::render($html))->toContain('callout-pearl')->toContain('Ask about steroids');
});

it('computes accessible theme colours', function () {
    $theme = app(ThemeService::class);

    expect($theme->contrastText('#12324f'))->toBe('#ffffff')
        ->and($theme->contrastRatio($theme->ensureContrast('#7ee0e0', '#ffffff'), '#ffffff'))->toBeGreaterThanOrEqual(4.5)
        ->and($theme->audit(['text' => '#cccccc', 'background' => '#ffffff', 'primary' => '#12324f']))->not->toBeEmpty()
        ->and($theme->build(['primary' => 'red;}</style>', 'custom_css' => '</style><script>']))->not->toContain('<script')->toContain('--color-ink:#');
});

it('estimates reading time automatically', function () {
    $article = Article::factory()->create(['body' => '<p>'.str_repeat('word ', 660).'</p>']);
    expect($article->reading_time)->toBe(3);
});
