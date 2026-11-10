<?php

namespace App\Http\Middleware;

use App\Http\Responses\NoDashboardAccess;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Dashboard-related pages outside the panel (draft previews, the dashboard language switch). */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        // Anyone who may use the dashboard (built-in team roles and roles with "dashboard.access").
        if (! ($request->user()?->is_active && $request->user()->canUseDashboard())) {
            return NoDashboardAccess::response($request);
        }

        // Same rule as the dashboard: nothing here before two-step verification is set up (when required).
        $panel = Filament::getPanel('admin');
        if ($panel->isMultiFactorAuthenticationRequired()) {
            Filament::setCurrentPanel($panel);

            return app(RequireStaffMfa::class)->handle($request, $next);
        }

        return $next($request);
    }
}
