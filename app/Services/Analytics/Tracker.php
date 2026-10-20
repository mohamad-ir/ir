<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\ClinicalCase;
use App\Models\PageView;
use App\Models\Question;
use App\Models\Video;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Tracker
{
    public const EVENTS = [
        'case_reveal', 'case_guess', 'video_play', 'video_progress', 'video_complete', 'share', 'outbound',
        'image_zoom', 'newsletter_signup', 'question_answer', 'search',
    ];

    public function __construct(protected VisitorHash $hash, protected GeoLocator $geo) {}

    public function shouldTrack(Request $request): bool
    {
        if (! Settings::get('analytics.enabled', true) || UserAgent::isBot($request->userAgent())) {
            return false;
        }

        return ! (Settings::get('analytics.exclude_staff', true) && $request->user()?->isStaff());
    }

    public function pageView(Request $request, array $payload): ?PageView
    {
        $path = '/'.ltrim(mb_substr((string) ($payload['u'] ?? '/'), 0, 490), '/');
        $visitor = $this->hash->for($request);
        [$type, $id] = $this->subject($payload['s'] ?? null);

        $referrer = filter_var($payload['r'] ?? null, FILTER_VALIDATE_URL) ?: null;
        $refHost = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : null;
        if ($refHost && $refHost === strtolower((string) $request->getHost())) {
            $refHost = null;
            $referrer = null;
        }

        $isUnique = ! PageView::where('visitor_hash', $visitor)->where('created_at', '>=', today())->exists();

        $view = PageView::create([
            'path' => $path,
            'viewable_type' => $type,
            'viewable_id' => $id,
            'visitor_hash' => $visitor,
            'is_unique' => $isUnique,
            'referrer_host' => $refHost ? preg_replace('/^www\./', '', mb_substr($refHost, 0, 250)) : null,
            'referrer_url' => $referrer ? mb_substr($referrer, 0, 500) : null,
            'utm_source' => $this->clean($payload['us'] ?? null, 100),
            'utm_medium' => $this->clean($payload['um'] ?? null, 100),
            'utm_campaign' => $this->clean($payload['uc'] ?? null, 150),
            'country' => $this->geo->country($request),
            'device' => UserAgent::device($request->userAgent(), isset($payload['w']) ? (int) $payload['w'] : null),
            'browser' => UserAgent::browser($request->userAgent()),
            'os' => UserAgent::os($request->userAgent()),
            'user_id' => $request->user()?->id,
            'created_at' => now(),
        ]);

        if ($id && in_array($type, ['case', 'article', 'video', 'page'], true)) {
            // Query-builder increment: no timestamps, revisions or activity entries.
            DB::table((new (Relation::getMorphedModel($type)))->getTable())->where('id', $id)->increment('views_count');
        }

        return $view;
    }

    public function event(Request $request, array $payload): ?AnalyticsEvent
    {
        $name = (string) ($payload['n'] ?? '');
        if (! in_array($name, self::EVENTS, true)) {
            return null;
        }

        [$type, $id] = $this->subject($payload['s'] ?? null);
        $props = collect(is_array($payload['p'] ?? null) ? $payload['p'] : [])
            ->take(8)->map(fn ($v) => is_scalar($v) ? mb_substr((string) $v, 0, 300) : null)->filter(fn ($v) => $v !== null)->all();

        $event = AnalyticsEvent::create([
            'name' => $name,
            'subject_type' => $type,
            'subject_id' => $id,
            'visitor_hash' => $this->hash->for($request),
            'path' => mb_substr((string) ($payload['u'] ?? ''), 0, 500) ?: null,
            'properties' => $props ?: null,
            'created_at' => now(),
        ]);

        $this->updateCounters($name, $type, $id, $props);

        return $event;
    }

    protected function updateCounters(string $name, ?string $type, ?int $id, array $props): void
    {
        if (! $id) {
            return;
        }

        if ($type === 'case' && $name === 'case_reveal') {
            ClinicalCase::whereKey($id)->toBase()->increment('reveals_count');
        } elseif ($type === 'case' && $name === 'case_guess') {
            $columns = ['guesses_count' => DB::raw('guesses_count + 1')];
            if (($props['correct'] ?? '') === '1' || ($props['correct'] ?? '') === 'true') {
                $columns['correct_guesses_count'] = DB::raw('correct_guesses_count + 1');
            }
            ClinicalCase::whereKey($id)->toBase()->update($columns);
        } elseif ($type === 'video' && $name === 'video_play') {
            Video::whereKey($id)->toBase()->increment('plays_count');
        } elseif ($type === 'video' && $name === 'video_complete') {
            Video::whereKey($id)->toBase()->increment('completions_count');
        }
    }

    /** @return array{0: ?string, 1: ?int} */
    protected function subject(mixed $subject): array
    {
        if (! is_array($subject) || empty($subject['type']) || empty($subject['id'])) {
            return [null, null];
        }

        $type = (string) $subject['type'];
        if (! in_array($type, ['case', 'article', 'video', 'question', 'page', 'topic', 'exam_topic'], true)) {
            return [null, null];
        }

        return [$type, (int) $subject['id']];
    }

    protected function clean(?string $value, int $length): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : mb_substr($value, 0, $length);
    }
}
