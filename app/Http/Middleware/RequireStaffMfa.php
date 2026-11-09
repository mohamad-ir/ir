<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Illuminate\Http\Request;

/**
 * “Require two-factor authentication for all team members” (Settings › General › Security),
 * decided on every request. Filament adds its own check to the dashboard routes only when
 * the routes are registered, so with cached routes (php artisan optimize / “Optimise for
 * speed”) a change of the setting would otherwise have no effect until the cache is rebuilt.
 */
class RequireStaffMfa extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Settings::get('security.require_staff_mfa', true)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
