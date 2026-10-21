<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use App\Services\Seo\SeoManager;
use App\Support\RichText;

class TopicController extends Controller
{
    public function index(SeoManager $seo)
    {
        $seo->title('Topics A–Z')
            ->description('Browse ophthalmology teaching material by disease and clinical topic: AMD, DME, CSR, ERM, macular hole, RVO, inherited retinal disease, uveitis and more.')
            ->breadcrumb('Topics', route('topics.index'));

        $topics = Topic::query()->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->withCount(['cases' => fn ($c) => $c->published()])])
            ->withCount(['cases' => fn ($c) => $c->published()])
            ->orderBy('sort_order')->orderBy('name')->get();

        return view('topics.index', ['topics' => $topics]);
    }

    public function show(string $slug, SeoManager $seo)
    {
        $topic = Topic::where('slug', $slug)->with(['parent', 'children', 'media', 'seo'])->firstOrFail();

        $seo->forModel($topic, 'website')
            ->breadcrumb('Topics', route('topics.index'));
        if ($topic->parent) {
            $seo->breadcrumb($topic->parent->name, $topic->parent->url());
        }
        $seo->breadcrumb($topic->name, $topic->url());
        $seo->schema(['@type' => 'CollectionPage', 'name' => $topic->label(), 'about' => ['@type' => 'MedicalCondition', 'name' => $topic->name, 'alternateName' => $topic->abbreviation], 'url' => $topic->url()]);

        return view('topics.show', [
            'topic' => $topic,
            'body' => RichText::render($topic->body),
            'cases' => ClinicalCase::published()->inTopic($topic)->with(['featuredMedia', 'topics', 'modalities'])->latest('published_at')->limit(9)->get(),
            'caseCount' => ClinicalCase::published()->inTopic($topic)->count(),
            'articles' => Article::published()->inTopic($topic)->with(['featuredMedia', 'category'])->latest('published_at')->limit(6)->get(),
            'videos' => Video::published()->inTopic($topic)->with(['thumbnailMedia', 'category'])->latest('published_at')->limit(6)->get(),
            'questions' => Question::published()->inTopic($topic)->latest('published_at')->limit(6)->get(),
            'trackSubject' => ['type' => 'topic', 'id' => $topic->id],
        ]);
    }
}
