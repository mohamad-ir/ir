<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Support\Facades\Artisan;

/** Fallback for hosts without cron access: an external service can call /cron/{token} every minute. */
class CronController extends Controller
{
    public function run(string $token)
    {
        $expected = config('cms.cron_token') ?: Settings::get('system.cron_token');
        abort_unless($expected && hash_equals((string) $expected, $token), 404);

        @set_time_limit(55);
        Artisan::call('schedule:run');

        return response('OK', 200, ['Content-Type' => 'text/plain']);
    }
}
