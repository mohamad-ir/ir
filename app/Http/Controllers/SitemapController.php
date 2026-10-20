<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapGenerator;
use App\Support\Settings;

class SitemapController extends Controller
{
    public function index(SitemapGenerator $sitemap)
    {
        return response($sitemap->index(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function type(string $type, SitemapGenerator $sitemap)
    {
        $xml = $sitemap->forType($type);
        abort_if($xml === null, 404);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = ['User-agent: *'];
        if (Settings::get('seo.default_noindex')) {
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/'.config('cms.admin_path'), '/account', '/_preview', '/search', '/install', '/newsletter/confirm', '/newsletter/unsubscribe'] as $path) {
                $lines[] = 'Disallow: '.$path;
            }
            $lines[] = 'Allow: /';
        }
        if ($extra = Settings::get('seo.robots_extra')) {
            $lines[] = '';
            $lines[] = trim($extra);
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function feed(SitemapGenerator $sitemap)
    {
        return response($sitemap->rss(), 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
