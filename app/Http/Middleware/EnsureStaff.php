<?php

namespace App\Http\Middleware;

use App\Http\Responses\NoDashboardAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        // Anyone who may use the dashboard (built-in team roles and roles with "dashboard.access").
        if (! ($request->user()?->is_active && $request->user()->canUseDashboard())) {
            return NoDashboardAccess::response($request);
        }

        return $next($request);
    }
}
