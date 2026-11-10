<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clear explanation (HTTP 403) for a signed-in person who may not use the dashboard: an inactive
 * account, or a role without “Sign in to the dashboard”. Instead of a bare “Access denied”.
 */
class NoDashboardAccess
{
    public static function response(Request $request): Response
    {
        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            abort(403, 'Your account does not have access to the dashboard.');
        }

        return response()->view('errors.no-dashboard-access', ['user' => $request->user()], 403);
    }
}
