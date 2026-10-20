<?php

use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\ExamTopic;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('renders the builder homepage with its sections', function () {
    ClinicalCase::factory()->create(['title' => 'A memorable OCT case', 'is_case_of_week' => true, 'case_of_week_date' => today()]);

    $this->get('/')
        ->assertOk()
        ->assertSee('one real case at a time', false)
        ->assertSee('A memorable OCT case')
        ->assertSee('application/ld+json', false);
});

it('renders every public section', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/cases', '/articles', '/videos', '/exam-prep', '/exam-prep/practice', '/topics', '/about', '/contact', '/courses', '/privacy', '/newsletter', '/login', '/register', '/search?q=csr', '/feed']);

it('shows published content and hides drafts and scheduled items', function () {
    $published = ClinicalCase::factory()->create();
    $draft = ClinicalCase::factory()->draft()->create();
    $scheduled = ClinicalCase::factory()->scheduled()->create();

    $this->get($published->urlPath())->assertOk()->assertSee($published->title)->assertSee('Your diagnosis');
    $this->get($draft->urlPath())->assertNotFound();
    $this->get($scheduled->urlPath())->assertNotFound();

    $this->travel(4)->days();
    $this->get($scheduled->urlPath())->assertOk();
});

it('renders articles with table of contents and linked citations', function () {
    $article = Article::factory()->create();

    $this->get($article->urlPath())
        ->assertOk()
        ->assertSee('href="#ref-1"', false)
        ->assertSee('id="introduction"', false)
        ->assertSee('"@type":"Article"', false);
});

it('renders videos with VideoObject schema and privacy-enhanced embeds', function () {
    $video = Video::factory()->create();

    $this->get($video->urlPath())
        ->assertOk()
        ->assertSee('"@type":"VideoObject"', false)
        ->assertSee('youtube-nocookie.com/embed/abcdefghijk', false);
});

it('renders topic hubs and exam topics', function () {
    $topic = Topic::where('abbreviation', 'CSR')->first();
    ClinicalCase::factory()->create()->topics()->attach($topic);
    $exam = ExamTopic::create(['title' => 'Uveitis', 'slug' => 'uveitis-exam', 'status' => 'published', 'published_at' => now()]);
    Question::factory()->create(['exam_topic_id' => $exam->id]);

    $this->get($topic->urlPath())->assertOk()->assertSee('1 case on');
    $this->get('/exam-prep/uveitis-exam')->assertOk()->assertSee('Practise 1 question');
});

it('gates members-only content', function () {
    $case = ClinicalCase::factory()->create(['access_level' => 'registered', 'diagnosis' => 'Secret diagnosis']);

    $this->get($case->urlPath())->assertOk()->assertSee('free for registered members')->assertDontSee('Secret diagnosis');
    $this->actingAs(\App\Models\User::factory()->create())->get($case->urlPath())->assertSee('Secret diagnosis');
});

it('serves sitemap, robots and feed', function () {
    $case = ClinicalCase::factory()->create();

    $this->get('/sitemap.xml')->assertOk()->assertSee('sitemap-cases.xml', false);
    $this->get('/sitemap-cases.xml')->assertOk()->assertSee($case->url(), false);
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:')->assertSee('Disallow: /admin');
    $this->get('/feed')->assertOk()->assertSee($case->title);
});

it('applies SEO overrides', function () {
    $case = ClinicalCase::factory()->create();
    $case->seo()->create(['meta_title' => 'Custom SEO title', 'meta_description' => 'Custom description', 'noindex' => true]);

    $this->get($case->urlPath())
        ->assertSee('<title>Custom SEO title | ', false)
        ->assertSee('content="Custom description"', false)
        ->assertSee('noindex', false);
});
