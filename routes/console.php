<?php

use App\Services\Analytics\AnalyticsQuery;
use App\Services\Backup\BackupService;
use App\Services\Newsletter\CampaignSender;
use App\Models\NotFoundLog;
use App\Support\Settings;
use Illuminate\Support\Facades\Schedule;

/*
 * Scheduled tasks. On DirectAdmin add ONE cron job running every minute:
 *   * * * * * /usr/local/bin/php /home/USER/domains/DOMAIN/app/artisan schedule:run >> /dev/null 2>&1
 */

Schedule::call(fn () => Settings::set('system.last_cron_at', now()->toIso8601String()))->everyMinute()->name('heartbeat');

Schedule::call(fn () => app(CampaignSender::class)->tick())->everyMinute()->name('newsletter')->withoutOverlapping(10);

Schedule::call(function () {
    app(\App\Services\Learning\WebinarReminders::class)->send();
    // Checkout sessions expire after 24 hours; tidy any order whose "expired" webhook never arrived.
    \App\Models\Order::where('status', 'pending')->where('created_at', '<', now()->subDays(2))->update(['status' => 'canceled']);
})->everyFiveMinutes()->name('webinar-reminders')->withoutOverlapping(10);

Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(5);

Schedule::call(function () {
    if (Settings::get('backup.schedule') === 'daily' || (Settings::get('backup.schedule') === 'weekly' && now()->isSunday())) {
        app(BackupService::class)->create(null, 'scheduled');
    }
})->dailyAt('03:10')->name('backup')->withoutOverlapping(120);

Schedule::call(function () {
    AnalyticsQuery::prune((int) Settings::get('analytics.retention_days', 730));
    NotFoundLog::where('hits', '<', 3)->where('last_seen_at', '<', now()->subDays(120))->delete();
})->dailyAt('04:20')->name('prune');
