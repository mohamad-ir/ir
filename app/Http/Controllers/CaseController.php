<?php

namespace App\Http\Controllers;

use App\Models\ClinicalCase;
use App\Services\Access\ContentAccess;
use App\Services\Seo\SchemaFactory;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index(SeoManager $seo)
    {
        $seo->title('OCT Case Library')
            ->description('Searchable library of real, de-identified OCT and retina teaching cases with findings, differential diagnosis, discussion and clinical pearls.')
            ->breadcrumb('OCT cases', route('cases.index'));

        return view('cases.index');
    }

    public function show(string $slug, Request $request, SeoManager $seo, ContentAccess $access)
    {
        $case = ClinicalCase::published()->where('slug', $slug)
            ->with(['topics', 'modalities', 'tags', 'author', 'featuredMedia', 'seo.ogImage'])
            ->firstOrFail();

        return $this->render($case, $request, $seo, $access);
    }

    public function caseOfWeek(Request $request, SeoManager $seo, ContentAccess $access)
    {
        $case = ClinicalCase::currentCaseOfWeek();

        if (! $case) {
            return redirect()->route('cases.index');
        }

        return redirect($case->urlPath());
    }

    public function render(ClinicalCase $case, Request $request, SeoManager $seo, ContentAccess $access, bool $preview = false)
    {
        $case->loadMissing(['topics', 'modalities', 'tags', 'author', 'featuredMedia']);

        $seo->forModel($case, 'article')
            ->image($case->seo?->ogImage ?? $case->coverImage())
            ->breadcrumb('OCT cases', route('cases.index'));
        if ($topic = $case->topics->first()) {
            $seo->breadcrumb($topic->name, $topic->url());
        }
        $seo->breadcrumb($case->title, $case->url())->schema(SchemaFactory::case($case));
        if ($preview) {
            $seo->noindex();
        }

        return view('cases.show', [
            'case' => $case,
            'canView' => $access->canView($case, $request->user()),
            'related' => $case->related(3)->load(['featuredMedia', 'topics', 'modalities']),
            'previous' => ClinicalCase::published()->where('published_at', '<', $case->published_at ?? now())->orderByDesc('published_at')->first(['id', 'title', 'slug']),
            'next' => ClinicalCase::published()->where('published_at', '>', $case->published_at ?? now())->orderBy('published_at')->first(['id', 'title', 'slug']),
            'trackSubject' => ['type' => 'case', 'id' => $case->id],
            'isPreview' => $preview,
        ]);
    }
}
