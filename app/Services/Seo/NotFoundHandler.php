<?php

namespace App\Services\Seo;

use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Called for every 404: applies a matching redirect from the Redirect manager,
 * otherwise records the broken URL in the 404 monitor and lets the error page render.
 */
class NotFoundHandler
{
    public function handle(Request $request): ?Response
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return null;
        }

        try {
            if (! Schema::hasTable('redirects')) {
                return null;
            }

            $path = Redirect::normalizePath($request->getPathInfo());
            $map = Cache::rememberForever('redirects.map', fn () => Redirect::where('is_active', true)->pluck('id', 'source_path')->all());

            if (isset($map[$path]) && ($redirect = Redirect::find($map[$path]))) {
                Redirect::whereKey($redirect->id)->update(['hits' => $redirect->hits + 1, 'last_hit_at' => now()]);

                if ($redirect->status_code === 410) {
                    return response()->view('errors.410', [], 410);
                }

                $target = $redirect->target_url;
                if (! str_starts_with($target, 'http')) {
                    $target = url($target).($request->getQueryString() ? '?'.$request->getQueryString() : '');
                }

                return redirect()->to($target, in_array($redirect->status_code, [301, 302, 307, 308]) ? $redirect->status_code : 301);
            }

            $this->log($request, $path);
        } catch (Throwable $e) {
            report($e);
        }

        return null;
    }

    protected function log(Request $request, string $path): void
    {
        if (preg_match('~\.(php|env|git|asp|aspx|jsp|cgi|sql|bak|ini|log)(/|$)|wp-(admin|login|content|includes)|xmlrpc~i', $path)) {
            return; // Ignore vulnerability scanners.
        }

        $hash = sha1($path);
        $updated = NotFoundLog::where('path_hash', $hash)->increment('hits', 1, ['last_seen_at' => now(), 'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 1000) ?: null]);

        if (! $updated) {
            NotFoundLog::create([
                'path_hash' => $hash,
                'path' => mb_substr($path, 0, 1000),
                'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 1000) ?: null,
                'hits' => 1,
                'last_seen_at' => now(),
            ]);
        }
    }
}
