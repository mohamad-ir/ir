<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Routes every request to the web installer until installation has completed. */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = Installer::isInstalled();
        $isInstallRoute = $request->is('install', 'install/*');

        if (! $installed && ! $isInstallRoute && ! $request->is('up')) {
            return redirect('/install');
        }

        if ($installed && $isInstallRoute) {
            abort(404);
        }

        return $next($request);
    }
}
