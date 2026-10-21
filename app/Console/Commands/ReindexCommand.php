<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\ExamTopic;
use App\Models\Page;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use App\Services\Search\SearchIndexer;
use App\Services\Seo\SitemapGenerator;
use Illuminate\Console\Command;

class ReindexCommand extends Command
{
    protected $signature = 'cms:reindex';

    protected $description = 'Rebuild the search index and clear cached sitemaps';

    public function handle(SearchIndexer $indexer): int
    {
        $n = $indexer->rebuild([ClinicalCase::class, Article::class, Video::class, Question::class, ExamTopic::class, Topic::class, Page::class]);
        SitemapGenerator::flush();
        $this->info("Indexed {$n} items.");

        return self::SUCCESS;
    }
}
