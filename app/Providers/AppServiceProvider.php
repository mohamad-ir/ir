<?php

namespace App\Providers;

use App\Models;
use App\Policies\MediaPolicy;
use App\Policies\RedirectPolicy;
use App\Policies\RolePolicy;
use App\Policies\SubscriberPolicy;
use App\Policies\TaxonomyPolicy;
use App\Services\PageBuilder\BlockRegistry;
use App\Services\Search\SearchIndexer;
use App\Services\Seo\SeoManager;
use App\Services\Seo\SitemapGenerator;
use App\Services\Theme\ThemeService;
use App\Support\Installer;
use App\Support\MailConfigurator;
use App\Support\Menus;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Until installation is finished there is no database to rely on: keep sessions and cache in
        // files and run jobs immediately, whatever .env says, so the web installer always opens.
        if (! Installer::isInstalled()) {
            config(['session.driver' => 'file', 'cache.default' => 'file', 'queue.default' => 'sync']);
        }

        $this->app->scoped(SeoManager::class);
        $this->app->singleton(SearchIndexer::class);
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(ThemeService::class);
    }

    public function boot(): void
    {
        // Developer aid: TRANSLATION_AUDIT=true logs dashboard text that has no Persian translation yet.
        if (config('app.translation_audit')) {
            app('translator')->handleMissingKeysUsing(function (string $key, array $replace, ?string $locale) {
                if ($locale === 'fa' && ! str_contains($key, '::')) {
                    file_put_contents(storage_path('logs/missing-fa.log'), $key."\n", FILE_APPEND | LOCK_EX);
                }

                return $key;
            });
        }

        // Dashboard labels that Filament derives from field names (e.g. "Port") follow the dashboard language too.
        foreach ([Field::class, Column::class, BaseFilter::class, Entry::class, Action::class] as $component) {
            $component::configureUsing(fn ($instance) => $instance->translateLabel());
        }

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
            'role' => Role::class,
            'course' => Models\Course::class,
            'lesson' => Models\Lesson::class,
            'webinar' => Models\Webinar::class,
            'membership_plan' => Models\MembershipPlan::class,
            'subscription' => Models\Subscription::class,
            'order' => Models\Order::class,
            'enrollment' => Models\Enrollment::class,
            'webinar_registration' => Models\WebinarRegistration::class,
            'gallery' => Models\Gallery::class,
            'gallery_item' => Models\GalleryItem::class,
            'certificate' => Models\Certificate::class,
            'banner' => Models\Banner::class,
            'banner_slide' => Models\BannerSlide::class,
            'comment' => Models\Comment::class,
            'popup' => Models\Popup::class,
            'career_stage' => Models\CareerStage::class,
        ]);

        if (str_starts_with((string) config('app.url'), 'https://') && $this->app->isProduction() && Installer::isInstalled()) {
            URL::forceScheme('https');
        }

        foreach ([Models\Topic::class, Models\Category::class, Models\Modality::class, Models\Tag::class] as $taxonomy) {
            Gate::policy($taxonomy, TaxonomyPolicy::class);
        }
        foreach ([Models\SearchSynonym::class, Models\NotFoundLog::class] as $seo) {
            Gate::policy($seo, RedirectPolicy::class);
        }
        Gate::policy(Models\Campaign::class, SubscriberPolicy::class);
        Gate::policy(Models\MediaFolder::class, MediaPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        // Clear cached sitemaps/feeds whenever public content changes.
        foreach ([Models\ClinicalCase::class, Models\Article::class, Models\Video::class, Models\Page::class, Models\Topic::class, Models\ExamTopic::class, Models\Course::class, Models\Webinar::class, Models\Gallery::class] as $content) {
            $content::saved(fn () => SitemapGenerator::flush());
            $content::deleted(fn () => SitemapGenerator::flush());
        }

        // Super administrators implicitly hold every permission.
        // Super administrators may do everything — except deleting a user, which always follows UserPolicy
        // (never yourself, never someone whose membership is still renewing in Stripe).
        Gate::before(fn ($user, string $ability, array $arguments = []) => $user->hasRole('super_admin')
            && ! ($ability === 'delete' && ($arguments[0] ?? null) instanceof Models\User) ? true : null);

        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(5)->by('form:'.$request->ip()),
            Limit::perDay(40)->by('form-day:'.$request->ip()),
        ]);
        RateLimiter::for('collect', fn (Request $request) => Limit::perMinute(120)->by('collect:'.$request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by('search:'.$request->ip()));

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            if ($event->user->canUseDashboard()) {
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
            $view->with('menus', Menus::all());
        });
    }
}
