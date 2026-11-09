<?php

namespace App\Http\Controllers;

use App\Services\Pwa\PwaService;

/** Installable app (PWA) endpoints. See App\Services\Pwa\PwaService. */
class PwaController extends Controller
{
    public function manifest(PwaService $pwa)
    {
        abort_unless($pwa->enabled(), 404);

        return response()->json($pwa->manifest(), 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * The service worker must be served from the site root to control every page. It is never
     * cached by the browser (no-cache) so new versions are found as soon as they are published;
     * when the app is switched off it unregisters itself (kill switch).
     */
    public function worker(PwaService $pwa)
    {
        $script = str_replace('__CONFIG__', json_encode($pwa->workerConfig(), JSON_UNESCAPED_SLASHES), (string) file_get_contents(resource_path('pwa/sw.js')));

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    /** Shown by the service worker when a page is not available offline. Contains no personal data. */
    public function offline()
    {
        return response()->view('pwa.offline')->header('Cache-Control', 'public, max-age=0, must-revalidate');
    }

    public function splash(PwaService $pwa, string $size)
    {
        [$width, $height] = array_map('intval', explode('x', $size) + [1 => 0]);
        $path = $pwa->enabled() ? $pwa->splash($width, $height) : null;
        abort_unless($path, 404);

        return response()->file($path, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=604800']);
    }
}
