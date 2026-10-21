<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\ExamTopic;
use App\Models\Page;
use App\Models\Topic;
use App\Models\Video;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

/** XML sitemaps (index + one per content type, with image/video extensions) and an RSS feed. Cached for an hour and cleared on publish. */
class SitemapGenerator
{
    public const TYPES = ['pages', 'cases', 'articles', 'videos', 'topics', 'exam', 'courses', 'webinars'];

    public static function flush(): void
    {
        Cache::forget('sitemap.index');
        Cache::forget('sitemap.rss');
        foreach (self::TYPES as $t) {
            Cache::forget('sitemap.'.$t);
        }
    }

    public function index(): string
    {
        return Cache::remember('sitemap.index', 3600, function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach (self::TYPES as $type) {
                $xml .= '  <sitemap><loc>'.e(route('sitemap.type', $type)).'</loc><lastmod>'.$this->lastModified($type).'</lastmod></sitemap>'."\n";
            }

            return $xml.'</sitemapindex>';
        });
    }

    protected function lastModified(string $type): string
    {
        $date = match ($type) {
            'cases' => ClinicalCase::published()->max('updated_at'),
            'articles' => Article::published()->max('updated_at'),
            'videos' => Video::published()->max('updated_at'),
            'topics' => Topic::max('updated_at'),
            'exam' => ExamTopic::published()->max('updated_at'),
            'courses' => \App\Models\Course::published()->max('updated_at'),
            'webinars' => \App\Models\Webinar::published()->max('updated_at'),
            default => Page::published()->max('updated_at'),
        };

        return ($date ? \Illuminate\Support\Carbon::parse($date) : now())->toAtomString();
    }

    public function entries(string $type): ?array
    {
        $noindex = fn ($q) => $q->whereDoesntHave('seo', fn ($s) => $s->where('noindex', true));

        return match ($type) {
            'pages' => array_merge(
                [['loc' => url('/'), 'lastmod' => now(), 'priority' => '1.0']],
                collect(['cases.index' => '0.9', 'videos.index' => '0.8', 'articles.index' => '0.8', 'exam.index' => '0.8', 'topics.index' => '0.7', 'courses.index' => '0.8', 'webinars.index' => '0.7', 'membership' => '0.6'])
                    ->map(fn ($p, $r) => ['loc' => route($r), 'lastmod' => null, 'priority' => $p])->values()->all(),
                Page::published()->where($noindex)->get()->reject(fn ($p) => $p->isHomepage())
                    ->map(fn ($p) => ['loc' => $p->url(), 'lastmod' => $p->updated_at, 'priority' => '0.6'])->values()->all(),
            ),
            'cases' => ClinicalCase::published()->where($noindex)->with('featuredMedia')->latest('published_at')->get()->map(fn ($c) => [
                'loc' => $c->url(), 'lastmod' => $c->updated_at, 'priority' => '0.8',
                'images' => $c->imageItems()->map(fn ($i) => ['loc' => $i['media']->url('lg'), 'title' => $i['caption'] ?? $i['media']->altText()])->all(),
            ])->all(),
            'articles' => Article::published()->where($noindex)->with('featuredMedia')->latest('published_at')->get()->map(fn ($a) => [
                'loc' => $a->url(), 'lastmod' => $a->updated_at, 'priority' => '0.7',
                'images' => $a->featuredMedia ? [['loc' => $a->featuredMedia->url('lg'), 'title' => $a->featuredMedia->altText()]] : [],
            ])->all(),
            'videos' => Video::published()->where($noindex)->latest('published_at')->get()->map(fn ($v) => [
                'loc' => $v->url(), 'lastmod' => $v->updated_at, 'priority' => '0.7',
                'video' => $v->thumbnail() && $v->isPublicAccess() ? [
                    'thumbnail' => $v->thumbnail(), 'title' => $v->title, 'description' => $v->seoDescription() ?: $v->title,
                    'player' => $v->isFilePlayer() ? null : preg_replace('/[?&]autoplay=1/', '', (string) $v->embedUrl()),
                    'content' => $v->isFilePlayer() ? $v->embedUrl() : null,
                    'duration' => $v->duration_seconds, 'date' => $v->published_at,
                ] : null,
            ])->all(),
            'topics' => Topic::query()->get()->map(fn ($t) => ['loc' => $t->url(), 'lastmod' => $t->updated_at, 'priority' => '0.6'])->all(),
            'exam' => array_merge(
                [['loc' => route('exam.practice'), 'lastmod' => null, 'priority' => '0.6']],
                ExamTopic::published()->get()->map(fn ($t) => ['loc' => $t->url(), 'lastmod' => $t->updated_at, 'priority' => '0.6'])->all(),
            ),
            'courses' => \App\Models\Course::published()->where($noindex)->with('featuredMedia')->latest('published_at')->get()->map(fn ($c) => [
                'loc' => $c->url(), 'lastmod' => $c->updated_at, 'priority' => '0.8',
                'images' => $c->featuredMedia ? [['loc' => $c->featuredMedia->url('lg'), 'title' => $c->featuredMedia->altText()]] : [],
            ])->all(),
            'webinars' => \App\Models\Webinar::published()->where($noindex)->latest('starts_at')->get()->map(fn ($w) => [
                'loc' => $w->url(), 'lastmod' => $w->updated_at, 'priority' => $w->hasEnded() ? '0.5' : '0.7',
            ])->all(),
            default => null,
        };
    }

    public function forType(string $type): ?string
    {
        if (! in_array($type, self::TYPES, true)) {
            return null;
        }

        return Cache::remember('sitemap.'.$type, 3600, function () use ($type) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">'."\n";

            foreach ($this->entries($type) as $e) {
                $xml .= '  <url><loc>'.e($e['loc']).'</loc>';
                if (! empty($e['lastmod'])) {
                    $xml .= '<lastmod>'.$e['lastmod']->toAtomString().'</lastmod>';
                }
                $xml .= '<priority>'.$e['priority'].'</priority>';
                foreach ($e['images'] ?? [] as $img) {
                    $xml .= '<image:image><image:loc>'.e($img['loc']).'</image:loc></image:image>';
                }
                if (! empty($e['video']) && ($e['video']['player'] || $e['video']['content'])) {
                    $v = $e['video'];
                    $xml .= '<video:video><video:thumbnail_loc>'.e($v['thumbnail']).'</video:thumbnail_loc><video:title>'.e($v['title']).'</video:title><video:description>'.e(mb_substr($v['description'], 0, 2000)).'</video:description>';
                    $xml .= $v['content'] ? '<video:content_loc>'.e($v['content']).'</video:content_loc>' : '<video:player_loc>'.e($v['player']).'</video:player_loc>';
                    if ($v['duration']) {
                        $xml .= '<video:duration>'.(int) $v['duration'].'</video:duration>';
                    }
                    if ($v['date']) {
                        $xml .= '<video:publication_date>'.$v['date']->toAtomString().'</video:publication_date>';
                    }
                    $xml .= '</video:video>';
                }
                $xml .= "</url>\n";
            }

            return $xml.'</urlset>';
        });
    }

    public function rss(): string
    {
        return Cache::remember('sitemap.rss', 3600, function () {
            $items = collect()
                ->merge(ClinicalCase::published()->latest('published_at')->limit(20)->get())
                ->merge(Article::published()->latest('published_at')->limit(20)->get())
                ->merge(Video::published()->latest('published_at')->limit(10)->get())
                ->sortByDesc('published_at')->take(30);

            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
                .'<title>'.e(Settings::get('site.name')).'</title><link>'.e(url('/')).'</link>'
                .'<description>'.e(Settings::get('site.description')).'</description><language>en-gb</language>'
                .'<atom:link href="'.e(route('feed')).'" rel="self" type="application/rss+xml"/>';
            foreach ($items as $item) {
                $xml .= '<item><title>'.e($item->title).'</title><link>'.e($item->url()).'</link><guid isPermaLink="true">'.e($item->url()).'</guid>'
                    .'<pubDate>'.$item->published_at->toRssString().'</pubDate><description>'.e($item->seoDescription()).'</description></item>';
            }

            return $xml.'</channel></rss>';
        });
    }
}
