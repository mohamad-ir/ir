<?php

use App\Enums\CampaignStatus;
use App\Filament\Pages\Settings\GeneralSettings;
use App\Filament\Pages\Settings\ThemeSettings;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Cases\Pages\CreateCase;
use App\Filament\Resources\Cases\Pages\EditCase;
use App\Filament\Resources\Cases\Pages\ListCases;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Mail\CampaignMail;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\ClinicalCase;
use App\Models\Page;
use App\Models\Subscriber;
use App\Services\Backup\BackupService;
use App\Services\Newsletter\CampaignSender;
use App\Services\PageBuilder\BlockRegistry;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('loads every dashboard page for a super administrator', function (string $path) {
    $this->seed(DatabaseSeeder::class);
    ClinicalCase::factory()->create();
    $this->actingAsStaff();

    $path = strtr($path, ['{case}' => ClinicalCase::value('id'), '{page}' => Page::value('id'), '{menu}' => \App\Models\Menu::value('id')]);
    $this->get('/'.config('cms.admin_path').$path)->assertOk();
})->with([
    '', '/cases', '/cases/create', '/cases/{case}/edit', '/articles', '/articles/create', '/videos/create', '/questions/create', '/exam-topics',
    '/pages', '/pages/{page}/edit', '/reusable-blocks/create', '/menus', '/menus/{menu}/edit', '/media', '/topics', '/categories', '/modalities', '/tags',
    '/profile-entries', '/users', '/roles', '/subscribers', '/campaigns/create', '/enquiries', '/redirects', '/not-found-logs',
    '/search-synonyms', '/activity-logs', '/insights/analytics', '/insights/google', '/settings/site', '/settings/theme',
    '/settings/header-footer', '/settings/seo', '/settings/email', '/settings/analytics', '/system/backups', '/system/health',
]);

it('keeps members and guests out of the dashboard', function () {
    $this->get('/'.config('cms.admin_path'))->assertRedirect();
    $this->seedRoles();
    $member = \App\Models\User::factory()->create();
    $member->assignRole('member');
    $this->actingAs($member)->get('/'.config('cms.admin_path'))->assertForbidden();
});

it('creates an OCT case from the dashboard', function () {
    $this->actingAsStaff();

    Livewire::test(CreateCase::class)
        ->fillForm(['title' => 'Dashboard created case', 'slug' => 'dashboard-created-case', 'diagnosis' => 'ERM', 'status' => 'published', 'difficulty' => 'beginner'])
        ->call('create')->assertHasNoFormErrors();

    $case = ClinicalCase::where('slug', 'dashboard-created-case')->firstOrFail();
    expect($case->isPublished())->toBeTrue()->and($case->author_id)->toBe(auth()->id());
    $this->get('/cases/dashboard-created-case')->assertOk();
});

it('submits contributor content for review instead of publishing', function () {
    $this->actingAsStaff('contributor');

    Livewire::test(CreateArticle::class)
        ->fillForm(['title' => 'Contributor article', 'slug' => 'contributor-article', 'body' => '<p>Hello</p>', 'status' => 'published'])
        ->call('create')->assertHasFormErrors(['status']);

    Livewire::test(CreateArticle::class)
        ->fillForm(['title' => 'Contributor article', 'slug' => 'contributor-article', 'body' => '<p>Hello</p>', 'status' => 'pending'])
        ->call('create')->assertHasNoFormErrors();

    $article = Article::where('slug', 'contributor-article')->firstOrFail();
    expect($article->status->value)->toBe('pending')->and($article->isPublished())->toBeFalse();
});

it('prevents authors from editing other people’s content', function () {
    $other = $this->staff('author');
    $case = ClinicalCase::factory()->create(['author_id' => $other->id]);
    $me = $this->actingAsStaff('author');

    expect($me->can('update', $case))->toBeFalse();
    $this->get('/'.config('cms.admin_path')."/cases/{$case->id}/edit")->assertForbidden();
    $this->actingAsStaff('editor');
    expect(auth()->user()->can('update', $case))->toBeTrue();
});

it('lists, filters and bulk-publishes cases', function () {
    $this->actingAsStaff();
    $drafts = ClinicalCase::factory()->count(2)->draft()->create();

    Livewire::test(ListCases::class)
        ->assertCanSeeTableRecords($drafts)
        ->callTableBulkAction('publish', $drafts);

    expect(ClinicalCase::published()->count())->toBe(2);
});

it('renders every page-builder block type without errors', function () {
    $this->seed(DatabaseSeeder::class);
    $registry = app(BlockRegistry::class);
    $blocks = collect(BlockRegistry::BLOCKS)->map(fn ($class) => ['type' => $class::key(), 'data' => ['heading' => 'Heading for '.$class::key()]])->all();

    $html = (string) $registry->render($blocks, ['preview' => true]);

    expect($html)->not->toContain('could not be rendered')->toContain('data-block="hero"')->toContain('data-block="faq"');
    expect(count(app(BlockRegistry::class)->filamentBlocks()))->toBe(count(BlockRegistry::BLOCKS));
});

it('hides builder sections marked hidden and emits FAQ schema', function () {
    $this->seed(DatabaseSeeder::class);
    $page = Page::create(['title' => 'FAQ page', 'slug' => 'faq-page', 'status' => 'published', 'published_at' => now(), 'blocks' => [
        ['type' => 'rich_text', 'data' => ['content' => '<p>Visible text</p>']],
        ['type' => 'rich_text', 'data' => ['content' => '<p>Hidden text</p>', '_hidden' => true]],
        ['type' => 'faq', 'data' => ['items' => [['question' => 'Is it free?', 'answer' => 'Yes']]]],
    ]]);

    $this->get('/faq-page')->assertOk()->assertSee('Visible text')->assertDontSee('Hidden text')->assertSee('"@type":"FAQPage"', false);
});

it('serves the live builder preview only to its owner', function () {
    $this->seed(DatabaseSeeder::class);
    $staff = $this->actingAsStaff();
    $component = Livewire::test(EditPage::class, ['record' => Page::where('slug', 'about')->first()->getRouteKey()]);
    $component->set('data.title', 'Unsaved title')->call('refreshPreview');
    $token = $component->get('previewToken');

    $this->get(route('preview.builder', $token))->assertOk();
    $this->actingAs(\App\Models\User::factory()->create())->get(route('preview.builder', $token))->assertForbidden();
});

it('saves settings and keeps secrets encrypted', function () {
    $this->actingAsStaff();

    Livewire::test(GeneralSettings::class)->set('data.site.name', 'Retina Teaching')->call('save')->assertHasNoErrors();
    expect(Settings::get('site.name'))->toBe('Retina Teaching');

    Settings::setMany(['mail.password' => 'super-secret'], ['mail.password']);
    expect(\App\Models\Setting::where('key', 'mail.password')->value('value'))->not->toContain('super-secret');
    Settings::flush();
    expect(Settings::get('mail.password'))->toBe('super-secret');
});

it('saves theme colours and regenerates the stylesheet', function () {
    $this->actingAsStaff();
    Livewire::test(ThemeSettings::class)->set('data.theme.primary', '#7a1f3d')->call('save')->assertHasNoErrors();

    expect(app(\App\Services\Theme\ThemeService::class)->css())->toContain('--color-ink:#7a1f3d');
});

it('sends campaigns in throttled batches with tracking', function () {
    Mail::fake();
    Settings::set('newsletter.send_rate_per_minute', 2);
    Subscriber::factory()->count(3)->create();
    Subscriber::factory()->create(['status' => 'unsubscribed']);
    $campaign = Campaign::create(['subject' => 'Weekly case', 'body' => '<p>See <a href="https://example.org/case">the case</a></p>', 'status' => 'draft']);

    $sender = app(CampaignSender::class);
    expect($sender->start($campaign))->toBe(3);
    $sender->sendBatch($campaign->fresh());
    expect($campaign->fresh()->sent_count)->toBe(2)->and($campaign->fresh()->status)->toBe(CampaignStatus::Sending);
    $sender->sendBatch($campaign->fresh());
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sent);
    Mail::assertSent(CampaignMail::class, 3);

    $recipient = $campaign->recipients()->with('subscriber')->first();
    $this->get(route('newsletter.click', [$campaign->id, $recipient->subscriber->token, 'u' => 'https://example.org/case']))->assertRedirect('https://example.org/case');
    expect($recipient->fresh()->clicked_at)->not->toBeNull();
});

it('creates a database backup archive', function () {
    ClinicalCase::factory()->create(['title' => 'Backed up case']);
    $service = app(BackupService::class);
    $name = $service->create(false, 'test');

    $zip = new ZipArchive;
    $zip->open($service->path($name));
    expect($zip->getFromName('database.sql'))->toContain('Backed up case')->toContain('CREATE TABLE');
    $zip->close();
    $service->delete($name);
});

it('installs and removes sample content', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $this->seed(DatabaseSeeder::class);
    $this->actingAsStaff();
    $this->seed(\Database\Seeders\DemoContentSeeder::class);

    expect(ClinicalCase::count())->toBe(10)->and(\App\Services\Demo\DemoContent::present())->toBeTrue();
    $this->get('/cases/acute-central-serous-chorioretinopathy')->assertOk()->assertSee('Central serous chorioretinopathy');

    \App\Services\Demo\DemoContent::remove();
    expect(ClinicalCase::count())->toBe(0)->and(\App\Models\Media::count())->toBe(0)->and(Page::count())->toBeGreaterThan(0);
});
