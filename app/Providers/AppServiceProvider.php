<?php

namespace App\Providers;

use App\Models;
use App\Services\PageBuilder\BlockRegistry;
use App\Services\Search\SearchIndexer;
use App\Services\Seo\SeoManager;
use App\Services\Theme\ThemeService;
use App\Support\MailConfigurator;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SeoManager::class);
        $this->app->singleton(SearchIndexer::class);
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(ThemeService::class);
    }

    public function boot(): void
    {

        Relation::enforceMorphMap([
            'user' => Models\User::class,
            'media' => Models\Media::class,
            'case' => Models\ClinicalCase::class,
            'article' => Models\Article::class,
            'video' => Models\Video::class,
            'question' => Models\Question::class,
            'exam_topic' => Models\ExamTopic::class,
            'topic' => Models\Topic::class,
            'page' => Models\Page::class,
            'reusable_block' => Models\ReusableBlock::class,
            'menu' => Models\Menu::class,
            'profile_entry' => Models\ProfileEntry::class,
            'subscriber' => Models\Subscriber::class,
            'campaign' => Models\Campaign::class,
            'enquiry' => Models\Enquiry::class,
            'redirect' => Models\Redirect::class,
            'setting' => Models\Setting::class,
            'role' => \Spatie\Permission\Models\Role::class,
        ]);

        if (str_starts_with((string) config('app.url'), 'https://') && $this->app->isProduction() && \App\Support\Installer::isInstalled()) {
            URL::forceScheme('https');
        }

        foreach ([Models\Topic::class, Models\Category::class, Models\Modality::class, Models\Tag::class] as $taxonomy) {
            Gate::policy($taxonomy, \App\Policies\TaxonomyPolicy::class);
        }
        foreach ([Models\SearchSynonym::class, Models\NotFoundLog::class] as $seo) {
            Gate::policy($seo, \App\Policies\RedirectPolicy::class);
        }
        Gate::policy(Models\Campaign::class, \App\Policies\SubscriberPolicy::class);
        Gate::policy(Models\MediaFolder::class, \App\Policies\MediaPolicy::class);
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);

        // Clear cached sitemaps/feeds whenever public content changes.
        foreach ([Models\ClinicalCase::class, Models\Article::class, Models\Video::class, Models\Page::class, Models\Topic::class, Models\ExamTopic::class] as $content) {
            $content::saved(fn () => \App\Services\Seo\SitemapGenerator::flush());
            $content::deleted(fn () => \App\Services\Seo\SitemapGenerator::flush());
        }

        // Super administrators implicitly hold every permission.
        Gate::before(fn ($user) => $user->hasRole('super_admin') ? true : null);

        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(5)->by('form:'.$request->ip()),
            Limit::perDay(40)->by('form-day:'.$request->ip()),
        ]);
        RateLimiter::for('collect', fn (Request $request) => Limit::perMinute(120)->by('collect:'.$request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by('search:'.$request->ip()));

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            if ($event->user->isStaff()) {
                Models\ActivityLog::record('login', $event->user->name.' signed in', $event->user);
            }
        });

        if (! $this->app->runningUnitTests()) {
            MailConfigurator::apply();
        }

        $timezone = Settings::get('site.timezone');
        if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.display_timezone' => $timezone]);
        }

        View::composer(['layouts.app', 'partials.*'], function ($view) {
            $view->with('menus', \App\Support\Menus::all());
        });
    }
}
