<?php

namespace App\Services\Popups;

use App\Models\Media;
use App\Models\Popup;
use App\Models\PopupStat;
use App\Services\Analytics\UserAgent;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Chooses the popups and bars a page may show, builds their browser settings
 * and records views, clicks, closes and sign-ups.
 */
class PopupService
{
    public const EVENTS = ['view' => 'views', 'click' => 'clicks', 'close' => 'closes', 'convert' => 'conversions'];

    /** Pages where popups never appear: sign-in, payment and system screens. */
    public const SYSTEM_PATHS = [
        '/login', '/register', '/logout', '/forgot-password', '/reset-password*', '/email/*', '/checkout*', '/stripe*',
        '/install*', '/offline', '/_preview*', '/newsletter/*', '/certificates/*/download*',
    ];

    public const PREVIEW_QUERY = 'popup_preview';

    public static function flush(): void
    {
        Cache::forget('popups.published');
    }

    public function enabled(): bool
    {
        return (bool) Settings::get('popups.enabled', true);
    }

    /** @return Collection<int, Popup> */
    public function published(): Collection
    {
        // Plain attribute arrays are cached (the cache never stores PHP objects); images are loaded in one query.
        $rows = Cache::rememberForever('popups.published', fn () => Popup::published()
            ->orderByDesc('priority')->orderByDesc('id')->get()->map->getAttributes()->all());

        $popups = Popup::hydrate($rows);
        if ($popups->whereNotNull('media_id')->isNotEmpty()) {
            $popups->load('media');
        }

        return $popups->toBase();
    }

    public function excludedPath(string $path): bool
    {
        $path = '/'.trim($path, '/');
        $admin = '/'.trim((string) config('cms.admin_path', 'admin'), '/');
        if ($path === $admin || str_starts_with($path, $admin.'/')) {
            return true;
        }

        $custom = preg_split('/\R/', (string) Settings::get('popups.exclude_paths', '')) ?: [];

        foreach ([...self::SYSTEM_PATHS, ...$custom] as $pattern) {
            if (Popup::pathMatches($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Popups and bars this request may show, highest priority first. The browser
     * still applies device, visitor, source, trigger and frequency rules.
     *
     * @return Collection<int, Popup>
     */
    public function forRequest(Request $request): Collection
    {
        if ($preview = $this->preview($request)) {
            return collect([$preview]);
        }

        if (! $this->enabled() || $request->method() !== 'GET' || UserAgent::isBot($request->userAgent())
            || $this->excludedPath($request->path())) {
            return collect();
        }

        $user = $request->user();
        $path = '/'.ltrim($request->path(), '/');

        return $this->published()
            ->filter(fn (Popup $p) => $p->isLive() && $p->matchesPath($path) && $p->matchesAudience($user))
            ->values();
    }

    /** A draft from the dashboard editor (staff only), shown immediately and never counted. */
    public function preview(Request $request): ?Popup
    {
        $token = $request->query(self::PREVIEW_QUERY);
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{20,64}$/', $token) || ! $request->user()?->can('popups.manage')) {
            return null;
        }

        $data = Cache::get('popup-preview:'.$token);
        if (! is_array($data)) {
            return null;
        }

        $popup = new Popup;
        $popup->forceFill(collect($data)->only($popup->getFillable())->all());
        $popup->id = 0;
        $popup->setRelation('media', ! empty($data['media_id']) ? Media::find($data['media_id']) : null);
        $popup->preview = true;

        return $popup;
    }

    public static function newPreviewToken(): string
    {
        return Str::random(40);
    }

    public static function storePreview(string $token, array $data): void
    {
        Cache::put('popup-preview:'.$token, $data, now()->addHours(2));
    }

    /** Settings the browser needs to decide whether and when to show a popup. Nothing personal is included. */
    public function clientConfig(Popup $popup): array
    {
        $preview = (bool) ($popup->preview ?? false);

        return [
            'id' => $popup->id,
            'rev' => $popup->revision ?? 1,
            'type' => $popup->type,
            'slot' => $popup->isBar() ? 'bar-'.$popup->position : ($popup->type === 'slide_in' ? 'corner' : 'modal'),
            'priority' => (int) $popup->priority,
            'trigger' => $preview ? 'load' : $popup->trigger,
            'value' => (int) ($popup->trigger_value ?? 0),
            'devices' => array_values((array) ($popup->devices ?: [])),
            'visitors' => $popup->visitors ?: 'all',
            'source' => $popup->source_match ? Str::lower($popup->source_match) : null,
            'frequency' => $preview ? 'page' : $popup->frequency,
            'days' => (int) ($popup->frequency_days ?: 1),
            'afterClose' => (int) ($popup->after_close_days ?? 0),
            'hideAfterGoal' => (bool) $popup->hide_after_conversion,
            'maxViews' => (int) ($popup->max_views ?? 0),
            'autoClose' => (int) ($popup->auto_close ?? 0),
            'ends' => $popup->ends_at?->getTimestampMs(),
            'overlayClose' => (bool) $popup->close_on_overlay,
            'form' => $popup->form,
            'preview' => $preview,
        ];
    }

    /** Global limits applied in the browser. */
    public function globalConfig(): array
    {
        return [
            'endpoint' => route('popups.track', [], false),
            'cooldown' => max(0, (int) Settings::get('popups.cooldown_minutes', 10)),
            'perSession' => max(0, (int) Settings::get('popups.max_per_session', 2)),
        ];
    }

    public function record(Popup $popup, string $event, ?string $device): void
    {
        $column = self::EVENTS[$event] ?? null;
        if (! $column || $popup->status !== 'published') {
            return;
        }

        $device = in_array($device, ['desktop', 'tablet', 'mobile'], true) ? $device : 'desktop';

        DB::transaction(function () use ($popup, $column, $device) {
            Popup::whereKey($popup->id)->toBase()->increment($column.'_count');

            PopupStat::query()->upsert(
                [['popup_id' => $popup->id, 'date' => now()->toDateString(), 'device' => $device, $column => 1]],
                ['popup_id', 'date', 'device'],
                [$column => DB::raw($column.' + 1')],
            );
        });

        // A campaign that just used its last impression disappears for everyone.
        if ($column === 'views' && $popup->max_impressions && $popup->views_count + 1 >= $popup->max_impressions) {
            self::flush();
        }
    }

    public function resetStats(Popup $popup): void
    {
        $popup->stats()->delete();
        $popup->forceFill(['views_count' => 0, 'clicks_count' => 0, 'closes_count' => 0, 'conversions_count' => 0, 'stats_reset_at' => now()])->save();
    }

    /** Visitors who already saw or closed the popup will see it again (their stored state is keyed by revision). */
    public function showAgain(Popup $popup): void
    {
        $popup->forceFill(['revision' => min(65000, ($popup->revision ?? 1) + 1)])->save();
    }

    /** Totals and daily series for the dashboard. */
    public function report(Popup $popup, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = $popup->stats()->where('date', '>=', $from->toDateString())->get();

        $labels = [];
        $series = ['views' => [], 'clicks' => [], 'conversions' => [], 'closes' => []];
        for ($d = $from->copy(); $d->lte(now()); $d->addDay()) {
            $key = $d->toDateString();
            $labels[] = $d->format('j M');
            $day = $rows->filter(fn ($r) => $r->date->toDateString() === $key);
            foreach ($series as $metric => $_) {
                $series[$metric][] = (int) $day->sum($metric);
            }
        }

        $devices = $popup->stats()->selectRaw('device, SUM(views) as views, SUM(clicks) as clicks, SUM(conversions) as conversions')
            ->groupBy('device')->get()->keyBy('device')
            ->map(fn ($r) => ['views' => (int) $r->views, 'clicks' => (int) $r->clicks, 'conversions' => (int) $r->conversions])->all();

        return ['labels' => $labels, 'series' => $series, 'devices' => $devices];
    }
}
