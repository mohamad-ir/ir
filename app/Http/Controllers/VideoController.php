<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\Access\ContentAccess;
use App\Services\Seo\SchemaFactory;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function index(SeoManager $seo)
    {
        $seo->title('Video Library')
            ->description('Educational videos on OCT interpretation, retina, uveitis, surgical cases, clinical examination and case discussions.')
            ->breadcrumb('Videos', route('videos.index'));

        return view('videos.index');
    }

    public function show(string $slug, Request $request, SeoManager $seo, ContentAccess $access)
    {
        $video = Video::published()->where('slug', $slug)
            ->with(['category', 'topics', 'tags', 'author', 'thumbnailMedia', 'videoMedia', 'seo.ogImage'])->firstOrFail();

        return $this->render($video, $request, $seo, $access);
    }

    public function render(Video $video, Request $request, SeoManager $seo, ContentAccess $access, bool $preview = false)
    {
        $video->loadMissing(['category', 'topics', 'tags', 'author', 'thumbnailMedia', 'videoMedia']);

        $seo->forModel($video, 'video.other')
            ->image($video->seo?->ogImage ?? $video->thumbnail())
            ->breadcrumb('Videos', route('videos.index'))
            ->breadcrumb($video->title, $video->url())
            ->schema(SchemaFactory::video($video));
        if ($preview) {
            $seo->noindex();
        }

        return view('videos.show', [
            'video' => $video,
            'canView' => $access->canView($video, $request->user()),
            'related' => $video->related(4)->load(['thumbnailMedia', 'category']),
            'startAt' => (int) $request->query('t', 0),
            'trackSubject' => ['type' => 'video', 'id' => $video->id],
            'isPreview' => $preview,
        ]);
    }
}
