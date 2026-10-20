<?php

namespace App\Http\Controllers;

use App\Services\Analytics\Tracker;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function collect(Request $request, Tracker $tracker)
    {
        if ($tracker->shouldTrack($request)) {
            $tracker->pageView($request, $this->payload($request));
        }

        return response()->noContent();
    }

    public function event(Request $request, Tracker $tracker)
    {
        if ($tracker->shouldTrack($request)) {
            $tracker->event($request, $this->payload($request));
        }

        return response()->noContent();
    }

    protected function payload(Request $request): array
    {
        $data = $request->json()->all();
        if (! $data) {
            $data = json_decode((string) $request->getContent(), true) ?: [];
        }

        return is_array($data) ? $data : [];
    }
}
