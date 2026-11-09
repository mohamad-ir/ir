<?php

use App\Filament\Resources\Popups\Pages\CreatePopup;
use App\Filament\Resources\Popups\Pages\EditPopup;
use App\Filament\Resources\Popups\Pages\ListPopups;
use App\Livewire\NewsletterForm;
use App\Models\Popup;
use App\Models\PopupStat;
use App\Models\Setting;
use App\Models\User;
use App\Services\Popups\PopupService;
use App\Services\Popups\PopupTemplates;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

beforeEach(function () {
    $this->seedRoles();
    $this->withHeader('User-Agent', BROWSER);
});

function popup(array $attributes = []): Popup
{
    return Popup::create([
        'name' => 'Test popup', 'type' => 'modal', 'status' => 'published', 'title' => 'Spring course launch',
        'trigger' => 'load', 'cta_label' => 'See it', 'cta_url' => '/courses', ...$attributes,
    ]);
}

function track(array $payload)
{
    return test()->postJson('/_p', $payload, ['User-Agent' => BROWSER]);
}

it('shows a published popup with its browser rules and the global limits', function () {
    $p = popup(['devices' => ['mobile'], 'visitors' => 'new', 'source_match' => 'Facebook', 'frequency' => 'days', 'frequency_days' => 3]);

    $html = $this->get('/')->assertOk()->assertSee('Spring course launch')->assertSee('id="popup-'.$p->id.'"', false)
        ->assertSee('window.__popups', false)->getContent();

    $config = app(PopupService::class)->clientConfig($p);
    expect($config)->toMatchArray(['type' => 'modal', 'slot' => 'modal', 'trigger' => 'load', 'devices' => ['mobile'], 'visitors' => 'new', 'source' => 'facebook', 'frequency' => 'days', 'days' => 3, 'preview' => false])
        ->and($html)->toContain('role="dialog"')->toContain('aria-modal="true"')->toContain('aria-labelledby="popup-'.$p->id.'-title"');
});

it('hides drafts, paused, scheduled, ended and exhausted campaigns', function () {
    popup(['title' => 'Draft one', 'status' => 'draft']);
    popup(['title' => 'Paused one', 'status' => 'paused']);
    popup(['title' => 'Future one', 'starts_at' => now()->addDay()]);
    popup(['title' => 'Past one', 'ends_at' => now()->subMinute()]);
    popup(['title' => 'Exhausted one', 'max_impressions' => 10])->forceFill(['views_count' => 10])->save();
    popup(['title' => 'Live one', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'max_impressions' => 10]);

    $this->get('/')->assertSee('Live one')->assertDontSee('Draft one')->assertDontSee('Paused one')
        ->assertDontSee('Future one')->assertDontSee('Past one')->assertDontSee('Exhausted one');
});

it('respects weekdays and daily hours in the site time zone, including overnight windows', function () {
    config(['app.display_timezone' => 'Europe/London']);
    $p = popup(['days' => [1, 2, 3, 4, 5], 'time_from' => '09:00', 'time_to' => '17:00']);
    $night = popup(['time_from' => '22:00', 'time_to' => '06:00']);

    expect($p->isLive(Carbon::parse('2026-11-09 10:00', 'Europe/London')))->toBeTrue()   // Monday
        ->and($p->isLive(Carbon::parse('2026-11-09 18:00', 'Europe/London')))->toBeFalse()
        ->and($p->isLive(Carbon::parse('2026-11-08 10:00', 'Europe/London')))->toBeFalse() // Sunday
        ->and($night->isLive(Carbon::parse('2026-11-09 23:30', 'Europe/London')))->toBeTrue()
        ->and($night->isLive(Carbon::parse('2026-11-09 05:00', 'Europe/London')))->toBeTrue()
        ->and($night->isLive(Carbon::parse('2026-11-09 12:00', 'Europe/London')))->toBeFalse()
        ->and($p->fresh()->stateLabel())->toBeIn(['Live', 'Outside its days/hours']);
});

it('targets pages with include and exclude patterns', function (array $include, array $exclude, string $path, bool $expected) {
    $p = new Popup(['include_paths' => $include, 'exclude_paths' => $exclude]);
    expect($p->matchesPath($path))->toBe($expected);
})->with([
    'everywhere' => [[], [], '/cases/csr', true],
    'homepage only' => [['/'], [], '/', true],
    'homepage only, other page' => [['/'], [], '/cases', false],
    'wildcard' => [['/courses/*'], [], '/courses/oct-basics', true],
    'wildcard does not match parent' => [['/courses/*'], [], '/courses', false],
    'full URL pasted' => [['https://example.com/cases'], [], '/cases', true],
    'exclude wins' => [[], ['/cases/*'], '/cases/csr', false],
    'exclude other page' => [[], ['/cases/*'], '/videos', true],
]);

it('targets guests, signed-in users, members and non-members', function () {
    popup(['title' => 'For guests', 'audience' => 'guests']);
    popup(['title' => 'For members', 'audience' => 'members']);
    popup(['title' => 'For non-members', 'audience' => 'non_members']);
    popup(['title' => 'For signed-in', 'audience' => 'signed_in']);

    $this->get('/')->assertSee('For guests')->assertDontSee('For members')->assertDontSee('For non-members')->assertDontSee('For signed-in');

    $this->actingAs(User::factory()->create());
    $this->get('/')->assertDontSee('For guests')->assertDontSee('For members')->assertSee('For non-members')->assertSee('For signed-in');

    $this->actingAs(makeMember());
    $this->get('/')->assertDontSee('For guests')->assertSee('For members')->assertDontSee('For non-members')->assertSee('For signed-in');
});

it('never shows popups in the dashboard, on sign-in or checkout pages, to bots, or when switched off', function () {
    popup(['title' => 'Everywhere popup']);

    $this->get('/login')->assertDontSee('Everywhere popup');
    $this->get('/register')->assertDontSee('Everywhere popup');
    $this->get('/', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->assertDontSee('Everywhere popup');

    Settings::set('popups.exclude_paths', "/cases\n/videos/*");
    $this->get('/cases')->assertDontSee('Everywhere popup');
    $this->get('/')->assertSee('Everywhere popup');

    Settings::set('popups.enabled', false);
    $this->get('/')->assertDontSee('Everywhere popup')->assertDontSee('window.__popups', false);

    $this->actingAsStaff();
    $this->get('/admin')->assertOk()->assertDontSee('Everywhere popup');
});

it('escapes content, cleans the rich text and rejects unsafe links', function () {
    popup([
        'title' => '<script>alert(1)</script>Offer',
        'body' => '<p>Hello <strong>doctor</strong></p><script>alert(2)</script><img src=x onerror=alert(3)>',
        'cta_url' => 'javascript:alert(4)',
    ]);

    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->not->toContain('<script>alert(1)')->not->toContain('alert(2)')->not->toContain('onerror=alert(3)')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;Offer')->toContain('<strong>doctor</strong>')
        ->not->toContain('href="javascript:');

    expect((new Popup(['cta_url' => '//evil.example']))->href())->toBeNull()
        ->and((new Popup(['cta_url' => 'courses']))->href())->toBe('/courses')
        ->and((new Popup(['cta_url' => '#popup-3']))->href())->toBe('#popup-3')
        ->and((new Popup(['color_scheme' => 'custom', 'bg_color' => '#111111']))->customStyle())->toContain('--popup-fg:#ffffff');

    $p = popup(['color_scheme' => 'custom', 'bg_color' => 'red;}body{display:none']);
    expect($p->fresh()->bg_color)->toBeNull();
});

it('renders announcement bars at the top and keeps every template valid', function () {
    foreach (array_keys(PopupTemplates::all()) as $key) {
        $p = Popup::create(['name' => $key, 'status' => 'published', ...PopupTemplates::values($key)]);
        expect(array_keys(Popup::POSITIONS[$p->type]))->toContain($p->position)
            ->and(array_keys(Popup::TRIGGERS))->toContain($p->trigger);
    }

    $html = $this->get('/courses')->assertOk()->getContent();
    $bar = strpos($html, 'New: Retina course with certificate');
    expect($bar)->not->toBeFalse()->toBeLessThan(strpos($html, '<header'));
});

it('counts views, clicks, closes and sign-ups per day and device', function () {
    $p = popup();

    track(['id' => $p->id, 'e' => 'view', 'd' => 'mobile'])->assertNoContent();
    track(['id' => $p->id, 'e' => 'view', 'd' => 'mobile']); // same visitor within seconds: ignored
    track(['id' => $p->id, 'e' => 'click', 'd' => 'mobile']);
    track(['id' => $p->id, 'e' => 'close', 'd' => 'desktop']);
    track(['id' => $p->id, 'e' => 'convert', 'd' => 'weird']); // unknown device counts as desktop
    track(['id' => $p->id, 'e' => 'hack', 'd' => 'mobile'])->assertNoContent();

    $p->refresh();
    expect([$p->views_count, $p->clicks_count, $p->closes_count, $p->conversions_count])->toBe([1, 1, 1, 1])
        ->and($p->ctr())->toBe(100.0)
        ->and(PopupStat::where('popup_id', $p->id)->where('device', 'mobile')->first()->only(['views', 'clicks']))->toBe(['views' => 1, 'clicks' => 1])
        ->and(PopupStat::where('popup_id', $p->id)->where('device', 'desktop')->first()->only(['closes', 'conversions']))->toBe(['closes' => 1, 'conversions' => 1]);

    $report = app(PopupService::class)->report($p, 7);
    expect($report['labels'])->toHaveCount(7)->and(array_sum($report['series']['views']))->toBe(1)->and($report['devices']['mobile']['views'])->toBe(1);
});

it('does not count drafts, bots or staff', function () {
    $draft = popup(['status' => 'draft']);
    track(['id' => $draft->id, 'e' => 'view', 'd' => 'desktop']);
    expect($draft->fresh()->views_count)->toBe(0);

    $p = popup();
    $this->postJson('/_p', ['id' => $p->id, 'e' => 'view'], ['User-Agent' => 'Googlebot/2.1'])->assertNoContent();
    $this->actingAsStaff();
    track(['id' => $p->id, 'e' => 'view', 'd' => 'desktop']);
    expect($p->fresh()->views_count)->toBe(0);
});

it('stops a limited campaign for everyone after its last impression', function () {
    $p = popup(['title' => 'First 2 visitors', 'max_impressions' => 2]);
    $p->forceFill(['views_count' => 1])->save();
    $this->get('/')->assertSee('First 2 visitors');

    track(['id' => $p->id, 'e' => 'view', 'd' => 'desktop']);
    $this->get('/')->assertDontSee('First 2 visitors');
    expect($p->fresh()->stateLabel())->toBe('Limit reached');
});

it('lets staff preview unsaved popups without counting them', function () {
    PopupService::storePreview($token = PopupService::newPreviewToken(), ['type' => 'slide_in', 'title' => 'Draft preview text', 'status' => 'draft', 'position' => 'bottom_left']);

    $this->get('/?popup_preview='.$token)->assertDontSee('Draft preview text'); // visitors: nothing

    $this->actingAsStaff();
    $html = $this->get('/?popup_preview='.$token)->assertSee('Draft preview text')->assertSee('Preview')->getContent();
    expect($html)->toContain('preview\\u0022:true')->not->toContain('href="#popup-0"');

    $saved = popup(['status' => 'draft', 'include_paths' => ['/cases']]);
    $this->get(route('preview.popup', $saved))->assertRedirectContains('/cases?popup_preview=');
});

it('can be managed from the dashboard by editors but not by authors', function () {
    popup(['name' => 'Listed popup']);

    $this->actingAs($this->staff('author'));
    $this->get('/admin/popups')->assertForbidden();

    $this->actingAs($this->staff('editor'));
    $this->get('/admin/popups')->assertOk()->assertSee('Listed popup');
    $this->get('/admin/popups/create')->assertOk()->assertSee('Live preview');
});

it('creates a popup from a template and validates links', function () {
    $this->actingAsStaff();

    Livewire::test(CreatePopup::class)
        ->fillForm(['template' => 'webinar'])
        ->assertFormSet(['type' => 'slide_in', 'position' => 'bottom_right', 'cta_url' => '/webinars', 'name' => 'Webinar reminder'])
        ->fillForm(['cta_url' => 'javascript:alert(1)'])
        ->call('create')
        ->assertHasFormErrors(['cta_url' => 'regex']);

    Livewire::test(CreatePopup::class)
        ->fillForm(['template' => 'newsletter', 'status' => 'published'])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Popup::latest('id')->first();
    expect($p->form)->toBe('newsletter')->and($p->trigger)->toBe('scroll')->and($p->template)->toBe('newsletter')
        ->and($p->created_by)->toBe(auth()->id());

    Livewire::test(EditPopup::class, ['record' => $p->getRouteKey()])->assertOk()->call('refreshPreview')->assertDispatched('popup-preview-updated');
});

it('resets statistics, shows popups again and saves the site-wide settings', function () {
    $this->actingAsStaff();
    $p = popup();
    track(['id' => $p->id, 'e' => 'view', 'd' => 'desktop']);

    Livewire::test(ListPopups::class)
        ->callTableAction('reset_stats', $p)
        ->callTableAction('show_again', $p)
        ->callAction('settings', ['enabled' => true, 'cooldown_minutes' => 30, 'max_per_session' => 1, 'exclude_paths' => '/contact']);

    $p->refresh();
    expect($p->views_count)->toBe(0)->and($p->stats()->count())->toBe(0)->and($p->revision)->toBe(2)->and($p->stats_reset_at)->not->toBeNull()
        ->and(Settings::get('popups.cooldown_minutes'))->toBe(30)->and(Settings::get('popups.max_per_session'))->toBe(1)
        ->and(app(PopupService::class)->excludedPath('/contact'))->toBeTrue();
});

it('moves the old header announcement setting into an announcement bar', function () {
    Settings::setMany(['header.announcement' => 'Old announcement text', 'header.announcement_url' => '/webinars', 'header.announcement_enabled' => true]);

    $migration = require database_path('migrations/2026_11_10_100000_create_popups_tables.php');
    (fn () => $this->moveOldAnnouncement())->call($migration);

    $bar = Popup::where('type', 'bar')->first();
    expect($bar)->title->toBe('Old announcement text')->cta_url->toBe('/webinars')->status->toBe('published')->position->toBe('top')
        ->and(Setting::where('key', 'like', 'header.announcement%')->count())->toBe(0);

    PopupService::flush();
    $this->get('/')->assertSee('Old announcement text');
});

it('reports newsletter sign-ups from a popup form to the page', function () {
    Livewire::test(NewsletterForm::class, ['source' => 'popup-7'])
        ->set('email', 'reader@example.com')
        ->call('subscribe')
        ->assertDispatched('newsletter-subscribed', source: 'popup-7');
});
