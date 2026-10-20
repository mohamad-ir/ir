<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use App\Services\Seo\SeoManager;
use App\Support\Settings;

class PageController extends Controller
{
    public function home(SeoManager $seo, BlockRegistry $blocks)
    {
        $page = Page::published()->find(Settings::get('site.homepage_id'))
            ?? Page::published()->where('slug', 'home')->first();

        if (! $page) {
            return view('pages.fallback-home');
        }

        $seo->forModel($page, 'website')->canonical(url('/'));
        $seo->title = $page->seo?->meta_title ?: null;
        foreach ($blocks->schema($page->blocks) as $schema) {
            $seo->schema($schema);
        }

        return view('pages.show', ['page' => $page, 'isHome' => true, 'trackSubject' => ['type' => 'page', 'id' => $page->id]]);
    }

    public function show(string $slug, SeoManager $seo, BlockRegistry $blocks)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        if ($page->isHomepage()) {
            return redirect('/', 301);
        }

        $seo->forModel($page, 'website')->breadcrumb($page->title, $page->url());
        foreach ($blocks->schema($page->blocks) as $schema) {
            $seo->schema($schema);
        }

        return view('pages.show', ['page' => $page, 'isHome' => false, 'trackSubject' => ['type' => 'page', 'id' => $page->id]]);
    }

    public function contact(SeoManager $seo, BlockRegistry $blocks)
    {
        if ($page = Page::published()->where('slug', 'contact')->first()) {
            return $this->show('contact', $seo, $blocks);
        }

        $seo->title('Contact & collaboration')->description('Scientific collaboration, speaking invitations, teaching and research enquiries.')->breadcrumb('Contact');

        return view('pages.contact');
    }
}
