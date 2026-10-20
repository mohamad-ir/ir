<?php

namespace App\Services\Search;

use App\Models\SearchDocument;
use App\Models\SearchSynonym;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Unified search over cases, articles, videos, questions, topics and pages.
 * Uses MySQL/MariaDB FULLTEXT with a medical abbreviation dictionary.
 */
class SearchService
{
    public const TYPES = [
        'case' => 'OCT cases',
        'article' => 'Articles',
        'video' => 'Videos',
        'question' => 'Exam questions',
        'exam_topic' => 'Exam topics',
        'topic' => 'Topics',
        'page' => 'Pages',
    ];

    /** @return array<int, string> Original query first, followed by synonym expansions. */
    public function expand(string $query): array
    {
        $q = mb_strtolower(trim(preg_replace('/\s+/', ' ', $query)));
        if ($q === '') {
            return [];
        }

        $dictionary = Cache::rememberForever('search.synonyms', fn () => SearchSynonym::where('is_active', true)->get()
            ->map(fn (SearchSynonym $s) => array_merge([$s->term], $s->synonymList()))->all());

        $terms = [$q];
        foreach ($dictionary as $group) {
            foreach ($group as $word) {
                if ($word === $q || preg_match('/(^|\W)'.preg_quote($word, '/').'($|\W)/u', $q)) {
                    foreach ($group as $alt) {
                        $terms[] = $word === $q ? $alt : trim(preg_replace('/(^|\W)'.preg_quote($word, '/').'($|\W)/u', '$1'.$alt.'$2', $q));
                    }
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($terms)));
    }

    protected function baseQuery(string $query, ?string $type = null): Builder
    {
        $terms = $this->expand($query);
        $builder = SearchDocument::query()
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->when($type && isset(self::TYPES[$type]), fn ($q) => $q->where('type', $type));

        if ($terms === []) {
            return $builder->whereRaw('1 = 0');
        }

        $fulltext = in_array(DB::getDriverName(), ['mysql', 'mariadb']);
        $natural = implode(' ', array_map(fn ($t) => preg_replace('/[+\-><()~*"@]+/', ' ', $t), $terms));

        $builder->where(function ($w) use ($terms, $fulltext, $natural) {
            if ($fulltext) {
                $w->orWhereRaw('MATCH(title, keywords, content) AGAINST (? IN NATURAL LANGUAGE MODE)', [$natural]);
            }
            foreach ($terms as $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $w->orWhere('title', 'like', $like)->orWhere('keywords', 'like', $like);
                if (! $fulltext) {
                    $w->orWhere('content', 'like', $like);
                }
            }
        });

        $titleLikes = implode(' + ', array_fill(0, count($terms), '(CASE WHEN title LIKE ? THEN 8 ELSE 0 END) + (CASE WHEN keywords LIKE ? THEN 4 ELSE 0 END)'));
        $bindings = [];
        foreach ($terms as $term) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $bindings[] = $like;
            $bindings[] = $like;
        }

        $score = $fulltext ? "MATCH(title, keywords, content) AGAINST (? IN NATURAL LANGUAGE MODE) + {$titleLikes}" : $titleLikes;
        $builder->select('search_documents.*')
            ->selectRaw("({$score}) as relevance", $fulltext ? [$natural, ...$bindings] : $bindings)
            ->orderByDesc('relevance')
            ->orderByDesc('published_at');

        return $builder;
    }

    public function search(string $query, ?string $type = null, int $perPage = 12): LengthAwarePaginator
    {
        return $this->baseQuery($query, $type)->paginate($perPage)->withQueryString();
    }

    /** @return array<string, int> */
    public function counts(string $query): array
    {
        $rows = SearchDocument::query()
            ->fromSub($this->baseQuery($query)->reorder()->select('search_documents.id', 'search_documents.type'), 'matches')
            ->selectRaw('type, COUNT(*) as aggregate')->groupBy('type')->pluck('aggregate', 'type');

        return collect(self::TYPES)->keys()->mapWithKeys(fn ($t) => [$t => (int) ($rows[$t] ?? 0)])->filter()->all();
    }

    public function suggest(string $query, int $limit = 7)
    {
        if (mb_strlen(trim($query)) < 2) {
            return collect();
        }

        return $this->baseQuery($query)->limit($limit)->get(['id', 'type', 'title', 'url', 'image_url', 'summary']);
    }
}
