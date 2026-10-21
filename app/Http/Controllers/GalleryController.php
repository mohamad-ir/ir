<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Services\Access\ContentAccess;
use App\Services\Seo\SchemaFactory;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(SeoManager $seo)
    {
        $seo->title('Image galleries')
            ->description('Curated collections of OCT, fundus and angiography images, organised by topic.')
            ->breadcrumb('Galleries', route('galleries.index'));

        $galleries = Gallery::published()->with(['coverMedia', 'items.media', 'topics'])->withCount('items')
            ->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('published_at')->get();

        return view('galleries.index', ['galleries' => $galleries]);
    }

    public function show(string $slug, Request $request, SeoManager $seo, ContentAccess $access)
    {
        $gallery = Gallery::published()->where('slug', $slug)->firstOrFail();

        return $this->render($gallery, $request, $seo, $access);
    }

    public function render(Gallery $gallery, Request $request, SeoManager $seo, ContentAccess $access, bool $preview = false)
    {
        $gallery->loadMissing(['coverMedia', 'items.media', 'topics', 'tags', 'author']);
        $items = $gallery->items->filter(fn ($i) => $i->media)->values();
        $canView = $access->canView($gallery, $request->user());

        $seo->forModel($gallery, 'website')
            ->image($gallery->seo?->ogImage ?? $gallery->cover())
            ->breadcrumb('Galleries', route('galleries.index'))
            ->breadcrumb($gallery->title, $gallery->url())
            ->schema(SchemaFactory::gallery($gallery, $canView && $access->level($gallery)->value === 'public' ? $items : collect()));
        if ($preview) {
            $seo->noindex();
        }

        return view('galleries.show', [
            'gallery' => $gallery,
            'items' => $items,
            'canView' => $canView,
            'related' => Gallery::published()->whereKeyNot($gallery->id)->with(['coverMedia', 'items.media'])->withCount('items')
                ->orderByDesc('is_featured')->latest('published_at')->limit(3)->get(),
            'isPreview' => $preview,
        ]);
    }
}
