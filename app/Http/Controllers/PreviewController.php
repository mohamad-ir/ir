<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Course;
use App\Models\ExamTopic;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Popup;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use App\Models\Webinar;
use App\Services\Access\ContentAccess;
use App\Services\Learning\LearningService;
use App\Services\Popups\PopupService;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Lets staff see drafts and scheduled items exactly as they will appear. */
class PreviewController extends Controller
{
    public function show(Request $request, string $type, int $id, SeoManager $seo, ContentAccess $access)
    {
        $seo->noindex();

        if ($type === 'topic') {
            return redirect(Topic::findOrFail($id)->urlPath());
        }

        $model = match ($type) {
            'case' => ClinicalCase::withTrashed()->findOrFail($id),
            'article' => Article::withTrashed()->findOrFail($id),
            'video' => Video::withTrashed()->findOrFail($id),
            'exam_topic' => ExamTopic::findOrFail($id),
            'page' => Page::withTrashed()->findOrFail($id),
            'course' => Course::withTrashed()->findOrFail($id),
            'webinar' => Webinar::withTrashed()->findOrFail($id),
            'gallery' => Gallery::withTrashed()->findOrFail($id),
            'question' => Question::withTrashed()->with(['media', 'examTopic'])->findOrFail($id),
        };
        // Drafts are only shown to people who may view this type of content in the dashboard.
        abort_unless($request->user()->can('view', $model), 403);

        return match ($type) {
            'case' => app(CaseController::class)->render($model, $request, $seo, $access, true),
            'article' => app(ArticleController::class)->render($model, $request, $seo, $access, true),
            'video' => app(VideoController::class)->render($model, $request, $seo, $access, true),
            'exam_topic' => app(ExamController::class)->renderTopic($model, $seo, true),
            'page' => $this->page($model, $seo),
            'course' => app(CourseController::class)->render($model, $request, $seo, $access, app(LearningService::class), true),
            'webinar' => app(WebinarController::class)->render($model, $request, $seo, $access, true),
            'gallery' => app(GalleryController::class)->render($model, $request, $seo, $access, true),
            'question' => view('preview.question', ['question' => $model, 'noTracking' => true]),
        };
    }

    /** Opens a saved popup on a page it targets, shown at once and without counting views. */
    public function popup(Popup $popup)
    {
        abort_unless(auth()->user()?->can('popups.manage'), 403);

        $token = PopupService::newPreviewToken();
        PopupService::storePreview($token, $popup->attributesToArray());

        $path = collect((array) $popup->include_paths)->first(fn ($p) => is_string($p) && str_starts_with($p, '/') && ! str_contains($p, '*')) ?? '/';

        return redirect($path.'?'.PopupService::PREVIEW_QUERY.'='.$token);
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
