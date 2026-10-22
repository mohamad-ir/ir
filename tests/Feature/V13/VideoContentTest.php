<?php

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Video;
use App\Support\RichText;
use App\Support\VideoEmbed;

beforeEach(fn () => $this->seedRoles());

/** A video inserted with the editor’s “Video” block, as the rich editor stores it. */
function videoPlaceholder(array $config): string
{
    return '<div data-type="customBlock" data-id="'.\App\RichEditor\VideoEmbedBlock::getId().'" data-config="'.e(json_encode($config)).'"></div>';
}

it('recognises YouTube, Vimeo and direct video links', function () {
    expect(VideoEmbed::fromUrl('https://youtu.be/abcdefghijk')['src'])->toContain('youtube-nocookie.com/embed/abcdefghijk')
        ->and(VideoEmbed::fromUrl('https://vimeo.com/123456')['src'])->toContain('player.vimeo.com/video/123456')
        ->and(VideoEmbed::fromUrl('https://cdn.test/clip.mp4')['kind'])->toBe('file')
        ->and(VideoEmbed::fromUrl('javascript:alert(1)'))->toBeNull();
});

it('expands a video placed in an article body into a player', function () {
    $article = Article::factory()->create(['body' => '<p>Intro</p>'.videoPlaceholder(['source' => 'url', 'url' => 'https://youtu.be/abcdefghijk', 'caption' => 'Watch the scan'])]);
    $this->get($article->urlPath())->assertSee('youtube-nocookie.com/embed/abcdefghijk', false)->assertSee('Watch the scan');
});

it('never exposes a members-only library video inside public content', function () {
    $video = Video::factory()->create(['access_level' => 'members', 'video_url' => 'https://www.youtube.com/watch?v=SECRETabcde']);
    $html = (string) RichText::render(videoPlaceholder(['source' => 'library', 'video_id' => $video->id]));

    expect($html)->not->toContain('SECRETabcde')->and($html)->toContain($video->urlPath());
    $this->actingAs(makeMember());
    expect((string) RichText::render(videoPlaceholder(['source' => 'library', 'video_id' => $video->id])))->toContain('SECRETabcde');
});

it('shows videos attached to an OCT case', function () {
    $case = ClinicalCase::factory()->create(['videos' => [['source' => 'url', 'url' => 'https://vimeo.com/987654', 'title' => 'Dynamic B-scan']]]);
    $this->get($case->urlPath())->assertSee('player.vimeo.com/video/987654', false)->assertSee('Dynamic B-scan');
});

it('sends a linked thumbnail instead of a player in emails', function () {
    $html = (string) RichText::renderForEmail(videoPlaceholder(['source' => 'url', 'url' => 'https://youtu.be/abcdefghijk']));
    expect($html)->not->toContain('<iframe')->and($html)->toContain('youtu');
});
