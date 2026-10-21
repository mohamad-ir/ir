<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\ExamTopic;
use App\Models\Page;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use App\Services\Access\ContentAccess;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Lets staff see drafts and scheduled items exactly as they will appear. */
class PreviewController extends Controller
{
    public function show(Request $request, string $type, int $id, SeoManager $seo, ContentAccess $access)
    {
        $seo->noindex();

        return match ($type) {
            'case' => app(CaseController::class)->render(ClinicalCase::withTrashed()->findOrFail($id), $request, $seo, $access, true),
            'article' => app(ArticleController::class)->render(Article::withTrashed()->findOrFail($id), $request, $seo, $access, true),
            'video' => app(VideoController::class)->render(Video::withTrashed()->findOrFail($id), $request, $seo, $access, true),
            'exam_topic' => app(ExamController::class)->renderTopic(ExamTopic::findOrFail($id), $seo, true),
            'topic' => redirect(Topic::findOrFail($id)->urlPath()),
            'page' => $this->page(Page::withTrashed()->findOrFail($id), $seo),
            'course' => app(CourseController::class)->render(\App\Models\Course::withTrashed()->findOrFail($id), $request, $seo, $access, app(\App\Services\Learning\LearningService::class), true),
            'webinar' => app(WebinarController::class)->render(\App\Models\Webinar::withTrashed()->findOrFail($id), $request, $seo, $access, true),
            'gallery' => app(GalleryController::class)->render(\App\Models\Gallery::withTrashed()->findOrFail($id), $request, $seo, $access, true),
            'question' => view('preview.question', ['question' => Question::withTrashed()->with(['media', 'examTopic'])->findOrFail($id), 'noTracking' => true]),
        };
    }

    protected function page(Page $page, SeoManager $seo)
    {
        $seo->title($page->title)->noindex();

        return view('pages.show', ['page' => $page, 'isHome' => $page->isHomepage(), 'isPreview' => true, 'noTracking' => true]);
    }

    /** Renders unsaved page-builder state stored by the dashboard's live preview panel. */
    public function builder(string $token, SeoManager $seo)
    {
        $state = Cache::get('builder-preview:'.$token);
        abort_unless(is_array($state) && ($state['user_id'] ?? null) === auth()->id(), 404);

        $page = new Page([
            'title' => $state['title'] ?? 'Preview',
            'blocks' => $state['blocks'] ?? [],
            'show_title' => (bool) ($state['show_title'] ?? false),
            'template' => $state['template'] ?? 'default',
        ]);
        $seo->title($page->title)->noindex();

        return view('pages.show', ['page' => $page, 'isHome' => (bool) ($state['is_home'] ?? false), 'isPreview' => true, 'noTracking' => true, 'livePreview' => true]);
    }
}
