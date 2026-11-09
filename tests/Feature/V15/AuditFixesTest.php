<?php

use App\Models\Popup;
use App\Services\Popups\PopupService;
use App\Services\Pwa\PwaService;
use App\Support\Installer;
use App\Support\Settings;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Regression tests for the 1.4.0 review: security hardening and production-mode fixes. */
beforeEach(fn () => $this->seedRoles());

const AUDIT_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

it('accepts popup beacons only from this website', function () {
    $p = Popup::create(['name' => 'Beacon', 'type' => 'modal', 'status' => 'published', 'title' => 'Hi', 'trigger' => 'load']);
    $headers = ['User-Agent' => AUDIT_UA];

    // Another website (cross-site fetch, or a form/text body that avoids the CORS preflight).
    $this->postJson('/_p', ['id' => $p->id, 'e' => 'view'], $headers + ['Sec-Fetch-Site' => 'cross-site'])->assertNoContent();
    $this->postJson('/_p', ['id' => $p->id, 'e' => 'view'], $headers + ['Origin' => 'https://evil.example'])->assertNoContent();
    $this->call('POST', '/_p', [], [], [], ['HTTP_USER_AGENT' => AUDIT_UA, 'CONTENT_TYPE' => 'text/plain'], json_encode(['id' => $p->id, 'e' => 'view']))->assertNoContent();
    expect($p->fresh()->views_count)->toBe(0);

    // This website.
    $this->postJson('/_p', ['id' => $p->id, 'e' => 'view', 'd' => 'mobile'], $headers + ['Sec-Fetch-Site' => 'same-origin', 'Origin' => 'http://localhost'])->assertNoContent();
    expect($p->fresh()->views_count)->toBe(1);
});

it('ends a capped campaign even when the cached popup list holds an old counter', function () {
    $p = Popup::create(['name' => 'Cap', 'type' => 'modal', 'status' => 'published', 'title' => 'Only three views', 'trigger' => 'load', 'max_impressions' => 3]);
    $service = app(PopupService::class);
    $service->published(); // cache: views_count = 0

    foreach (range(1, 3) as $i) {
        $service->record($service->published()->firstWhere('id', $p->id), 'view', 'desktop');
    }

    $this->withHeader('User-Agent', AUDIT_UA)->get('/')->assertDontSee('Only three views');
});

it('renders top announcement bars visible from the server (no layout shift) and other popups hidden', function () {
    Popup::create(['name' => 'Bar', 'type' => 'bar', 'position' => 'top', 'status' => 'published', 'title' => 'Top bar text', 'trigger' => 'load']);
    Popup::create(['name' => 'Modal', 'type' => 'modal', 'status' => 'published', 'title' => 'Modal text', 'trigger' => 'delay']);

    $html = $this->withHeader('User-Agent', AUDIT_UA)->get('/')->getContent();
    $barTag = str($html)->before('data-popup-type="bar"')->afterLast('<div')->toString();
    $modalTag = str($html)->before('data-popup-type="modal"')->afterLast('<div')->toString();

    expect($barTag)->not->toContain('x-cloak')->toContain('initial\\u0022:true')
        ->and($modalTag)->toContain('x-cloak');
});

it('keeps public pages working when the popups table does not exist yet', function () {
    Schema::drop('popup_stats');
    Schema::drop('popups');
    PopupService::flush();

    $this->withHeader('User-Agent', AUDIT_UA)->get('/')->assertOk();
});

it('serves the service worker and manifest without a session or cookies', function () {
    $this->get('/sw.js')->assertOk()->assertCookieMissing(config('session.cookie'))->assertCookieMissing('XSRF-TOKEN');
    $this->get('/manifest.webmanifest')->assertOk()->assertCookieMissing(config('session.cookie'));
});

it('precaches only the icons in use, so a missing default icon cannot block installation', function () {
    $precache = app(PwaService::class)->precache();
    foreach ($precache as $file) {
        expect($file)->toStartWith('/');
    }
    expect($precache)->toContain('/offline');

    $js = $this->get('/sw.js')->getContent();
    expect($js)->toContain('Promise.allSettled')->toContain('event.preloadResponse');
});

it('does not draw a launch screen when the icon file is missing', function () {
    Settings::set('branding.icon_set', ['files' => ['icon-512' => 'branding/missing/icon-512.png']]);

    expect(app(PwaService::class)->splash(1170, 2532))->toBeNull();
    $this->get('/pwa/splash/1170x2532.png')->assertNotFound();
});

it('refuses to change the dashboard language from another website', function () {
    $user = $this->actingAsStaff();

    $this->get('/admin/locale/fa', ['Sec-Fetch-Site' => 'cross-site'])->assertForbidden();
    expect($user->fresh()->admin_locale)->not->toBe('fa');

    $this->get('/admin/locale/fa', ['Sec-Fetch-Site' => 'same-origin'])->assertRedirect();
    expect($user->fresh()->admin_locale)->toBe('fa');
});

it('sends every email in English even from a Persian dashboard', function () {
    app()->setLocale('fa');
    foreach (glob(app_path('Mail/*.php')) as $file) {
        $class = 'App\\Mail\\'.basename($file, '.php');
        expect((new ReflectionClass($class))->getDefaultProperties()['locale'])->toBe('en', $class);
    }
});

it('trusts forwarded IP addresses only from known proxies', function () {
    $ip = function (string $remote) {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $remote, 'HTTP_X_FORWARDED_FOR' => '203.0.113.7']);
        (new TrustProxies)->handle($request, fn ($r) => $r);

        return $request->ip();
    };

    expect($ip('198.51.100.20'))->toBe('198.51.100.20')   // direct visitor faking X-Forwarded-For
        ->and($ip('127.0.0.1'))->toBe('203.0.113.7')      // proxy on the same server
        ->and($ip('162.158.10.1'))->toBe('203.0.113.7');  // Cloudflare
});

it('knows the site is installed when the configuration is cached', function () {
    // A cached configuration means .env is not read: env() returns nothing.
    putenv('APP_INSTALLED');
    unset($_ENV['APP_INSTALLED'], $_SERVER['APP_INSTALLED']);
    try {
        config(['app.installed' => true]);
        expect(Installer::isInstalled())->toBeTrue();
        config(['app.installed' => false]);
        expect(Installer::isInstalled())->toBe(is_file(Installer::lockFile()));
    } finally {
        putenv('APP_INSTALLED=true');
        $_ENV['APP_INSTALLED'] = $_SERVER['APP_INSTALLED'] = 'true';
    }
});
