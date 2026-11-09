<?php

use App\Models\User;
use App\Services\Pwa\PwaService;
use App\Support\Settings;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seedRoles());

it('publishes a standard, installable Web App Manifest', function () {
    $response = $this->get('/manifest.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json');
    $m = $response->json();

    expect($m)->toMatchArray(['id' => '/', 'start_url' => '/?source=pwa', 'scope' => '/', 'display' => 'standalone', 'lang' => 'en'])
        ->and($m['name'])->toBe('OCT Academy')
        ->and($m['background_color'])->toMatch('/^#[0-9a-f]{6}$/')
        ->and(collect($m['icons'])->pluck('sizes')->all())->toContain('192x192', '512x512')
        ->and(collect($m['icons'])->pluck('purpose')->all())->toContain('any', 'maskable')
        ->and($m['shortcuts'])->not->toBeEmpty();
});

it('adds the app tags to public pages only', function () {
    $this->get('/')->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('apple-touch-startup-image', false)
        ->assertSee('apple-mobile-web-app-capable', false);

    $this->actingAsStaff();
    $this->get('/admin')->assertOk()->assertDontSee('rel="manifest"', false)->assertDontSee('window.__pwa', false);
});

it('serves a root-scoped, never-cached service worker with the privacy rules built in', function () {
    $response = $this->get('/sw.js')->assertOk()->assertHeader('Service-Worker-Allowed', '/');
    expect($response->headers->get('Content-Type'))->toContain('javascript')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');

    $js = $response->getContent();
    $config = json_decode(str($js)->after('const CONFIG = ')->before(";\n")->toString(), true);
    expect($config['enabled'])->toBeTrue()
        ->and($config['version'])->toBe(app(PwaService::class)->version())
        ->and($config['privatePrefixes'])->toContain('/admin', '/account', '/checkout', '/login', '/stripe', '/livewire')
        ->and($config['precache'])->toContain('/offline');
    foreach ($config['precache'] as $file) {
        if (str_starts_with($file, '/build/') || str_starts_with($file, '/icons/')) {
            expect(public_path(ltrim($file, '/')))->toBeFile();
        }
    }
});

it('marks only guest views of public pages as safe for offline reading', function () {
    $this->get('/cases')->assertOk()->assertHeader('X-Offline-Cacheable', '1');
    $this->get(\App\Models\Article::factory()->create()->urlPath())->assertOk()->assertHeader('X-Offline-Cacheable', '1');
    $this->get('/login')->assertHeaderMissing('X-Offline-Cacheable');
    $this->get('/search?q=csr')->assertHeaderMissing('X-Offline-Cacheable');
    $this->get('/missing-page-xyz')->assertHeaderMissing('X-Offline-Cacheable');

    $this->actingAs(User::factory()->create());
    $response = $this->get('/cases')->assertOk()->assertHeaderMissing('X-Offline-Cacheable');
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
    $this->get('/account')->assertHeaderMissing('X-Offline-Cacheable');
});

it('treats every private area as private', function (string $path, bool $private) {
    expect(app(PwaService::class)->isPrivatePath($path))->toBe($private);
})->with([
    ['/admin', true], ['/admin/cases/1/edit', true], ['/account/profile', true], ['/checkout/success', true],
    ['/login', true], ['/stripe/webhook', true], ['/webinars/live-case/join', true], ['/courses/oct/lessons/2', true],
    ['/media/avatars/2/x-96.webp', true], ['/livewire-abc/update', true], ['/certificates/ABC', true],
    ['/', false], ['/cases', false], ['/cases/csr', false], ['/articles/x', false], ['/courses/oct', false], ['/media/uploads/a.webp', false],
]);

it('shows an offline page without personal data', function () {
    $this->actingAs(User::factory()->create(['name' => 'Private Person']));
    $this->get('/offline')->assertOk()->assertSee('You are offline')->assertDontSee('Private Person');
});

it('creates iOS launch screens for known devices only', function () {
    Storage::fake('public');
    $response = $this->get('/pwa/splash/1170x2532.png')->assertOk()->assertHeader('Content-Type', 'image/png');
    expect(getimagesize($response->getFile()->getPathname()))->toMatchArray([0 => 1170, 1 => 2532]);
    $this->get('/pwa/splash/1000x1000.png')->assertNotFound();
});

it('changes version when the app shell changes, so installed apps update', function () {
    $pwa = app(PwaService::class);
    $before = $pwa->version();
    Settings::set('theme.background', '#ffffff');
    expect($pwa->version())->not->toBe($before);
});

it('can be switched off: no manifest, no tags, and the worker unregisters itself', function () {
    Settings::set('pwa.enabled', false);
    $this->get('/manifest.webmanifest')->assertNotFound();
    $this->get('/')->assertDontSee('rel="manifest"', false)->assertDontSee('pwa-suggest-title', false);
    $js = $this->get('/sw.js')->assertOk()->getContent();
    expect($js)->toContain('"enabled":false')->toContain('self.registration.unregister()');
});

it('offers the install suggestion only when enabled', function () {
    $this->get('/')->assertSee('suggest\\u0022:true', false);
    Settings::set('pwa.install_prompt', false);
    $this->get('/')->assertSee('suggest\\u0022:false', false);
});

it('lets administrators control the app in Theme & branding', function () {
    $this->actingAsStaff();
    $this->get('/admin/settings/theme')->assertOk()->assertSee('Installable app (PWA)');
    Livewire\Livewire::test(\App\Filament\Pages\Settings\ThemeSettings::class)->set('data.pwa.install_prompt', false)->call('save')->assertHasNoErrors();
    expect(Settings::get('pwa.install_prompt'))->toBeFalse();
});
