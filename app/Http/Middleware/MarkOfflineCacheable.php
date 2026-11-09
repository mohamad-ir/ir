<?php

namespace App\Http\Middleware;

use App\Services\Pwa\PwaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells the service worker which pages may be kept for offline reading: successful public HTML
 * pages viewed by a guest. Pages seen while signed in can contain personal or members-only
 * content, so they are never marked — and are sent with "Cache-Control: private, no-store".
 */
class MarkOfflineCacheable
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        if ($response->getStatusCode() === 200 && ! app(PwaService::class)->isPrivatePath($request->getPathInfo())) {
            $response->headers->set('X-Offline-Cacheable', '1');
        }

        return $response;
    }
}
