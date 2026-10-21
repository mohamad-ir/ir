<?php

namespace App\Http\Controllers;

use App\Models\SearchQuery;
use App\Services\Analytics\VisitorHash;
use App\Services\Search\SearchService;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, SearchService $search, SeoManager $seo)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 120);
        $type = $request->query('type');
        $type = is_string($type) && array_key_exists($type, SearchService::TYPES) ? $type : null;

        $seo->title($q !== '' ? "Search: {$q}" : 'Search')->noindex()->breadcrumb('Search');

        $results = $q !== '' ? $search->search($q, $type) : null;
        $counts = $q !== '' ? $search->counts($q) : [];

        if ($q !== '' && ! $request->query('page')) {
            SearchQuery::create([
                'query' => mb_strtolower($q),
                'results_count' => array_sum($counts),
                'visitor_hash' => app(VisitorHash::class)->for($request),
                'created_at' => now(),
            ]);
        }

        return view('search.index', [
            'q' => $q,
            'type' => $type,
            'results' => $results,
            'counts' => $counts,
            'expansions' => $q !== '' ? array_slice($search->expand($q), 1, 4) : [],
            'noTracking' => false,
        ]);
    }

    public function suggest(Request $request, SearchService $search)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 120);
        $labels = ['case' => 'Case', 'article' => 'Article', 'video' => 'Video', 'question' => 'Question', 'exam_topic' => 'Exam', 'topic' => 'Topic', 'page' => 'Page'];

        $results = $search->suggest($q)->map(fn ($doc) => [
            'title' => $doc->title,
            'url' => url($doc->url),
            'type' => $labels[$doc->type] ?? 'Page',
            'typeShort' => mb_substr($labels[$doc->type] ?? 'Pg', 0, 3),
            'image' => $doc->image_url,
            'summary' => str($doc->summary)->limit(90)->toString(),
        ])->values();

        return response()->json(['results' => $results])->header('Cache-Control', 'public, max-age=60');
    }
}
