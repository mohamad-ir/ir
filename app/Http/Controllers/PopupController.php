<?php

namespace App\Http\Controllers;

use App\Models\Popup;
use App\Services\Analytics\UserAgent;
use App\Services\Analytics\VisitorHash;
use App\Services\Popups\PopupService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Receives popup views, clicks, closes and sign-ups (sent with navigator.sendBeacon). Stores no personal data. */
class PopupController extends Controller
{
    public function track(Request $request, PopupService $popups, VisitorHash $hash)
    {
        // Only this website's own pages may report: browsers send these headers with sendBeacon/fetch,
        // and other websites cannot send a JSON body without a (refused) CORS preflight.
        if (! $this->fromThisSite($request)) {
            return response()->noContent();
        }

        $data = $request->json()->all();
        $event = (string) ($data['e'] ?? '');
        $id = (int) ($data['id'] ?? 0);

        if (! $id || ! array_key_exists($event, PopupService::EVENTS) || UserAgent::isBot($request->userAgent())
            || (Settings::get('analytics.exclude_staff', true) && $request->user()?->isStaff())) {
            return response()->noContent();
        }

        $popup = $popups->published()->firstWhere('id', $id) ?? Popup::published()->find($id);

        // The same visitor cannot count the same event twice within a few seconds (double clicks, retries).
        if ($popup && Cache::add('popup-hit:'.$id.':'.$event.':'.$hash->for($request), 1, 5)) {
            $popups->record($popup, $event, is_string($data['d'] ?? null) ? $data['d'] : null);
        }

        return response()->noContent();
    }

    protected function fromThisSite(Request $request): bool
    {
        if (! $request->isJson()) {
            return false;
        }
        $site = $request->headers->get('Sec-Fetch-Site');
        if ($site !== null && ! in_array($site, ['same-origin', 'none'], true)) {
            return false;
        }
        $origin = $request->headers->get('Origin');

        return $origin === null || $origin === 'null' ? $site !== 'cross-site' : strcasecmp((string) parse_url($origin, PHP_URL_HOST), $request->getHost()) === 0;
    }
}
