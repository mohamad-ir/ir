<?php

use App\Http\Controllers;
use App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;

// Web installer (only reachable before installation completes)
Route::prefix('install')->name('install.')->controller(Controllers\InstallController::class)->group(function () {
    Route::get('/', 'welcome')->name('welcome');
    Route::get('/database', 'database')->name('database');
    Route::post('/database', 'saveDatabase')->name('database.save');
    Route::get('/admin', 'admin')->name('admin');
    Route::post('/admin', 'saveAdmin')->name('admin.save');
    Route::get('/done', 'done')->name('done');
});

Route::get('/', [Controllers\PageController::class, 'home'])->name('home');

// OCT case library
Route::get('/cases', [Controllers\CaseController::class, 'index'])->name('cases.index');
Route::get('/case-of-the-week', [Controllers\CaseController::class, 'caseOfWeek'])->name('cases.week');
Route::get('/cases/{slug}', [Controllers\CaseController::class, 'show'])->name('cases.show');

// Articles
Route::get('/articles', [Controllers\ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/category/{category:slug}', [Controllers\ArticleController::class, 'category'])->name('articles.category');
Route::get('/articles/{slug}', [Controllers\ArticleController::class, 'show'])->name('articles.show');

// Videos
Route::get('/videos', [Controllers\VideoController::class, 'index'])->name('videos.index');
Route::get('/videos/{slug}', [Controllers\VideoController::class, 'show'])->name('videos.show');

// FRCS / FRCOphth
Route::get('/exam-prep', [Controllers\ExamController::class, 'index'])->name('exam.index');
Route::get('/exam-prep/practice', [Controllers\ExamController::class, 'practice'])->name('exam.practice');
Route::get('/exam-prep/{slug}', [Controllers\ExamController::class, 'topic'])->name('exam.topic');

// Certificates of completion (public verification)
Route::get('/certificates/{code}', [Controllers\CertificateController::class, 'show'])->name('certificates.show')->middleware('throttle:60,1');
Route::get('/certificates/{code}/download', [Controllers\CertificateController::class, 'download'])->name('certificates.download')->middleware(['auth', 'throttle:20,1']);

// Photo galleries
Route::get('/galleries', [Controllers\GalleryController::class, 'index'])->name('galleries.index');
Route::get('/galleries/{slug}', [Controllers\GalleryController::class, 'show'])->name('galleries.show');

// Courses, webinars and membership
Route::get('/courses', [Controllers\CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{slug}', [Controllers\CourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{slug}/lessons/{lesson}', [Controllers\CourseController::class, 'lesson'])->name('courses.lesson');
Route::get('/webinars', [Controllers\WebinarController::class, 'index'])->name('webinars.index');
Route::get('/webinars/{slug}', [Controllers\WebinarController::class, 'show'])->name('webinars.show');
Route::get('/webinars/{slug}/calendar.ics', [Controllers\WebinarController::class, 'calendar'])->name('webinars.calendar');
Route::get('/membership', [Controllers\MembershipController::class, 'show'])->name('membership');

// Visitor's preferred currency for prices and checkout
Route::post('/currency', function (\Illuminate\Http\Request $request) {
    $currency = strtoupper((string) $request->input('currency'));
    abort_unless(in_array($currency, \App\Support\Currency::enabled(), true), 422);

    return back()->withCookie(cookie()->forever(\App\Support\Currency::COOKIE, $currency));
})->name('currency')->middleware('throttle:30,1');

// Banner click counter (sent with navigator.sendBeacon)
Route::post('/banner-click/{slide}', function (\App\Models\BannerSlide $slide) {
    $slide->increment('clicks_count');

    return response()->noContent();
})->name('banner.click')->middleware('throttle:30,1');

// Stripe webhooks (signature-verified, CSRF-exempt)
Route::post('/stripe/webhook', Controllers\StripeWebhookController::class)->name('stripe.webhook')->middleware('throttle:120,1');

// Topics
Route::get('/topics', [Controllers\TopicController::class, 'index'])->name('topics.index');
Route::get('/topics/{slug}', [Controllers\TopicController::class, 'show'])->name('topics.show');

// Search
Route::get('/search', [Controllers\SearchController::class, 'index'])->name('search')->middleware('throttle:search');
Route::get('/search/suggest', [Controllers\SearchController::class, 'suggest'])->name('search.suggest')->middleware('throttle:search');

// Newsletter
Route::get('/newsletter', [Controllers\NewsletterController::class, 'show'])->name('newsletter.show');
Route::get('/newsletter/confirm/{token}', [Controllers\NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{token}', [Controllers\NewsletterController::class, 'unsubscribeConfirm'])->name('newsletter.unsubscribe.confirm');
Route::get('/newsletter/open/{campaign}/{token}', [Controllers\NewsletterController::class, 'open'])->name('newsletter.open');
Route::get('/newsletter/click/{campaign}/{token}', [Controllers\NewsletterController::class, 'click'])->name('newsletter.click');

// Contact (falls back to a built-in page when no CMS page uses the slug)
Route::get('/contact', [Controllers\PageController::class, 'contact'])->name('contact');

// First-party analytics beacons
Route::post('/_a', [Controllers\AnalyticsController::class, 'collect'])->name('analytics.collect')->middleware('throttle:collect');
Route::post('/_e', [Controllers\AnalyticsController::class, 'event'])->name('analytics.event')->middleware('throttle:collect');

// SEO endpoints
Route::get('/sitemap.xml', [Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{type}.xml', [Controllers\SitemapController::class, 'type'])->name('sitemap.type')->where('type', '[a-z_]+');
Route::get('/robots.txt', [Controllers\SitemapController::class, 'robots'])->name('robots');
Route::get('/feed', [Controllers\SitemapController::class, 'feed'])->name('feed');

// Web cron fallback for hosts without cron access
Route::get('/cron/{token}', [Controllers\CronController::class, 'run'])->name('cron')->middleware('throttle:10,1');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'show'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [Auth\RegisterController::class, 'show'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'register'])->middleware('throttle:forms');
    Route::get('/forgot-password', [Auth\PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordController::class, 'email'])->name('password.email')->middleware('throttle:forms');
    Route::get('/reset-password/{token}', [Auth\PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [Auth\PasswordController::class, 'update'])->name('password.update')->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [Auth\LoginController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [Auth\VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [Auth\VerificationController::class, 'verify'])->name('verification.verify')->middleware(['signed', 'throttle:6,1']);
    Route::post('/email/verification-notification', [Auth\VerificationController::class, 'send'])->name('verification.send')->middleware('throttle:6,1');

    Route::prefix('account')->name('account.')->controller(Controllers\AccountController::class)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
        Route::put('/password', 'updatePassword')->name('password.update');
        Route::get('/export', 'export')->name('export');
        Route::delete('/', 'destroy')->name('destroy');
        Route::post('/bookmarks/{type}/{id}', 'toggleBookmark')->name('bookmark')->where('type', 'case|article|video|question');
    });

    // Learning
    Route::post('/courses/{slug}/enroll', [Controllers\CourseController::class, 'enroll'])->name('courses.enroll');
    Route::post('/courses/{slug}/lessons/{lesson}/complete', [Controllers\CourseController::class, 'complete'])->name('courses.complete');
    Route::post('/webinars/{slug}/register', [Controllers\WebinarController::class, 'register'])->name('webinars.register')->middleware('throttle:forms');
    Route::post('/webinars/{slug}/cancel', [Controllers\WebinarController::class, 'cancel'])->name('webinars.cancel');
    Route::get('/webinars/{slug}/join', [Controllers\WebinarController::class, 'join'])->name('webinars.join');

    // Payments (Stripe Checkout)
    Route::prefix('checkout')->name('checkout.')->controller(Controllers\CheckoutController::class)->group(function () {
        Route::post('/membership/{plan:slug}', 'membership')->name('membership')->middleware('throttle:10,1');
        Route::post('/course/{slug}', 'course')->name('course')->middleware('throttle:10,1');
        Route::post('/webinar/{slug}', 'webinar')->name('webinar')->middleware('throttle:10,1');
        Route::get('/success', 'success')->name('success');
        Route::get('/cancel', 'cancel')->name('cancel');
    });
    Route::get('/account/billing', [Controllers\AccountController::class, 'billing'])->name('account.billing');
    Route::post('/account/billing/portal', [Controllers\CheckoutController::class, 'portal'])->name('account.billing.portal')->middleware('throttle:10,1');

    // Staff previews of drafts and the live page-builder canvas
    Route::middleware('staff')->prefix('_preview')->name('preview.')->controller(Controllers\PreviewController::class)->group(function () {
        Route::get('/builder/{token}', 'builder')->name('builder');
        Route::get('/{type}/{id}', 'show')->name('show')->where('type', 'case|article|video|page|exam_topic|topic|question|course|webinar|gallery');
    });
});

// CMS pages (must stay last)
Route::get('/{slug}', [Controllers\PageController::class, 'show'])
    ->where('slug', '^(?!'.preg_quote(config('cms.admin_path'), '/').'|livewire|filament|install|_preview|media|build|storage|account|cron|checkout|stripe)[a-z0-9]+(?:-[a-z0-9]+)*$')
    ->name('page.show');
