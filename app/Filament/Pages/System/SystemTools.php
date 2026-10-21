<?php

namespace App\Filament\Pages\System;

use App\Models\ActivityLog;
use App\Services\Analytics\AnalyticsQuery;
use App\Services\Demo\DemoContent;
use App\Services\Newsletter\CampaignSender;
use App\Services\Search\SearchIndexer;
use App\Services\Seo\SitemapGenerator;
use App\Support\Permissions;
use App\Support\Settings;
use App\Support\SystemHealth;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class SystemTools extends Page
{
    protected static ?string $slug = 'system/health';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Health & maintenance';

    protected static ?string $title = 'System health & maintenance';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.system';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage') ?? false;
    }

    public function getViewData(): array
    {
        return [
            'requirements' => SystemHealth::requirements(),
            'runtime' => SystemHealth::runtime(),
            'maintenance' => app()->isDownForMaintenance(),
            'cronUrl' => ($t = Settings::get('system.cron_token') ?: config('cms.cron_token')) ? route('cron', $t) : null,
            'demo' => DemoContent::summary(),
            'pending' => $this->pendingMigrations(),
            'cronLine' => '* * * * * '.PHP_BINARY.' '.base_path('artisan').' schedule:run >> /dev/null 2>&1',
        ];
    }

    protected function done(string $title, ?string $body = null): void
    {
        ActivityLog::record('system', $title);
        Notification::make()->success()->title($title)->body($body)->send();
    }

    public function pendingMigrations(): int
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles([database_path('migrations')]);
        $ran = $migrator->getRepository()->getRan();

        return count(array_diff(array_keys($files), $ran));
    }

    public function applyUpdates(): void
    {
        @set_time_limit(300);
        Artisan::call('migrate', ['--force' => true]);
        Permissions::sync();
        Artisan::call('optimize:clear');
        Settings::flush();
        $this->done('Database updates applied', trim(Artisan::output()) ?: null);
    }

    public function clearCaches(): void
    {
        Artisan::call('optimize:clear');
        Settings::flush();
        SitemapGenerator::flush();
        $this->done('Caches cleared');
    }

    public function optimize(): void
    {
        Artisan::call('optimize');
        Artisan::call('filament:optimize');
        $this->done('Application optimised', 'Configuration, routes, views and dashboard components are now cached for speed.');
    }

    public function rebuildSearch(): void
    {
        $n = app(SearchIndexer::class)->rebuild(SearchIndexer::MODELS);
        $this->done("Search index rebuilt ({$n} items)");
    }

    public function runScheduler(): void
    {
        Artisan::call('schedule:run');
        app(CampaignSender::class)->tick();
        $this->done('Scheduled tasks run');
    }

    public function pruneAnalytics(): void
    {
        $n = AnalyticsQuery::prune((int) Settings::get('analytics.retention_days', 730));
        $this->done("Removed {$n} old analytics records");
    }

    public function generateCronToken(): void
    {
        Settings::set('system.cron_token', Str::random(40));
        $this->done('Web-cron address generated');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('maintenance')
                ->label(fn () => app()->isDownForMaintenance() ? 'Bring site back online' : 'Enable maintenance mode')
                ->color(fn () => app()->isDownForMaintenance() ? 'success' : 'warning')
                ->icon('heroicon-o-power')
                ->requiresConfirmation()
                ->modalDescription(fn () => app()->isDownForMaintenance() ? null : 'Visitors will see a “back shortly” page. You will get a private link that lets you keep browsing the site.')
                ->action(function () {
                    if (app()->isDownForMaintenance()) {
                        Artisan::call('up');
                        $this->done('Website is online');
                    } else {
                        $secret = Str::random(24);
                        Artisan::call('down', ['--secret' => $secret, '--render' => 'errors::503', '--retry' => 60]);
                        ActivityLog::record('system', 'Maintenance mode enabled');
                        Notification::make()->warning()->title('Maintenance mode enabled')->body('Bypass link: '.url($secret).' — open it once to keep access in this browser. The dashboard remains available through it.')->persistent()->send();
                    }
                }),
            Action::make('removeDemo')->label('Remove sample content')->icon('heroicon-o-trash')->color('danger')
                ->visible(fn () => DemoContent::present() && auth()->user()->isSuperAdmin())
                ->requiresConfirmation()
                ->modalHeading('Remove all sample content?')
                ->modalDescription('Deletes the sample cases, articles, videos, questions, CV entries and synthetic images created during installation. Pages, menus, topics and settings are kept. Content you created yourself is never touched.')
                ->schema([TextInput::make('confirm')->label('Type DELETE to confirm')->required()->in(['DELETE'])])
                ->action(function () {
                    $n = DemoContent::remove();
                    $this->done("Sample content removed ({$n} items)");
                }),
        ];
    }
}
