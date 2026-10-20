<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Services\Access\ContentAccess;
use App\Services\Seo\SchemaFactory;
use App\Services\Seo\SeoManager;
use App\Support\RichText;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(SeoManager $seo)
    {
        $seo->title('Articles')
            ->description('Educational articles on OCT interpretation, medical retina and uveitis for ophthalmologists in training and practice.')
            ->breadcrumb('Articles', route('articles.index'));

        return view('articles.index', ['category' => null]);
    }

    public function category(Category $category, SeoManager $seo)
    {
        abort_unless($category->type === 'article', 404);

        $seo->title($category->name.' — Articles')
            ->description($category->description ?: "Articles about {$category->name}.")
            ->breadcrumb('Articles', route('articles.index'))
            ->breadcrumb($category->name, route('articles.category', $category));

        return view('articles.index', ['category' => $category]);
    }

    public function show(string $slug, Request $request, SeoManager $seo, ContentAccess $access)
    {
        $article = Article::published()->where('slug', $slug)
            ->with(['category', 'topics', 'tags', 'author', 'featuredMedia', 'seo.ogImage'])->firstOrFail();

        return $this->render($article, $request, $seo, $access);
    }

    public function render(Article $article, Request $request, SeoManager $seo, ContentAccess $access, bool $preview = false)
    {
        $article->loadMissing(['category', 'topics', 'tags', 'author', 'featuredMedia']);

        $seo->forModel($article, 'article')
            ->image($article->seo?->ogImage ?? $article->featuredMedia)
            ->breadcrumb('Articles', route('articles.index'));
        if ($article->category) {
            $seo->breadcrumb($article->category->name, route('articles.category', $article->category));
        }
        $seo->breadcrumb($article->title, $article->url())->schema(SchemaFactory::article($article));
        if ($preview) {
            $seo->noindex();
        }

        $content = RichText::process($article->body, $article->references ?? []);

        return view('articles.show', [
            'article' => $article,
            'content' => $content,
            'canView' => $access->canView($article, $request->user()),
            'related' => $article->related(3)->load(['featuredMedia', 'category']),
            'trackSubject' => ['type' => 'article', 'id' => $article->id],
            'isPreview' => $preview,
        ]);
    }
}
