<?php

namespace App\Console\Commands;

use App\Services\Search\SearchIndexer;
use App\Services\Seo\SitemapGenerator;
use Illuminate\Console\Command;

class ReindexCommand extends Command
{
    protected $signature = 'cms:reindex';

    protected $description = 'Rebuild the search index and clear cached sitemaps';

    public function handle(SearchIndexer $indexer): int
    {
        $n = $indexer->rebuild(SearchIndexer::MODELS);
        SitemapGenerator::flush();
        $this->info("Indexed {$n} items.");

        return self::SUCCESS;
    }
}
