<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\Popups\PopupService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A popup, corner slide-in or announcement bar. Rules the server can check
 * (status, schedule, pages, audience) are evaluated in PopupService; rules that
 * only the browser knows (device, new/returning visitor, source, trigger,
 * frequency) are evaluated by resources/js/popups.js.
 */
class Popup extends Model
{
    use LogsActivity;

    public const TYPES = ['modal' => 'Popup (centre of the screen)', 'slide_in' => 'Slide-in (corner)', 'bar' => 'Announcement bar'];

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'paused' => 'Paused'];

    public const POSITIONS = [
        'modal' => ['center' => 'Centre', 'bottom' => 'Bottom sheet on phones, centre on computers'],
        'slide_in' => ['bottom_right' => 'Bottom right', 'bottom_left' => 'Bottom left'],
        'bar' => ['top' => 'Top of the page', 'bottom' => 'Bottom of the screen'],
    ];

    public const SIZES = ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'];

    public const SCHEMES = ['default' => 'Page colours', 'brand' => 'Brand (dark)', 'accent' => 'Accent', 'highlight' => 'Highlight', 'custom' => 'Custom colours'];

    public const ANIMATIONS = ['fade' => 'Fade', 'slide' => 'Slide', 'zoom' => 'Zoom', 'none' => 'None'];

    public const IMAGE_STYLES = ['top' => 'Above the text', 'side' => 'Beside the text', 'background' => 'Background'];

    public const TRIGGERS = [
        'load' => 'As soon as the page opens',
        'delay' => 'After a few seconds',
        'scroll' => 'After scrolling part of the page',
        'exit' => 'When the visitor is about to leave',
        'idle' => 'After the visitor is inactive',
        'pageviews' => 'After a number of pages in the visit',
        'click' => 'Only when a link or button opens it',
    ];

    public const AUDIENCES = [
        'everyone' => 'Everyone',
        'guests' => 'Visitors who are not signed in',
        'signed_in' => 'Signed-in users',
        'members' => 'Members with an active membership',
        'non_members' => 'Signed-in users without a membership',
    ];

    public const DEVICES = ['desktop' => 'Computers', 'tablet' => 'Tablets', 'mobile' => 'Phones'];

    public const VISITORS = ['all' => 'All visitors', 'new' => 'First visit only', 'returning' => 'Returning visitors only'];

    public const FREQUENCIES = [
        'page' => 'On every page view',
        'session' => 'Once per visit',
        'days' => 'Once every few days',
        'once' => 'Only once per visitor',
    ];

    public const FORMS = ['none' => 'No form', 'newsletter' => 'Newsletter signup'];

    public const WEEKDAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    protected $fillable = [
        'name', 'type', 'status', 'priority', 'template',
        'eyebrow', 'title', 'body', 'media_id', 'image_style', 'cta_label', 'cta_url', 'cta_new_tab', 'dismiss_label', 'form',
        'position', 'size', 'color_scheme', 'bg_color', 'text_color', 'button_color', 'animation', 'show_overlay', 'close_on_overlay', 'show_close', 'auto_close',
        'trigger', 'trigger_value',
        'include_paths', 'exclude_paths', 'audience', 'devices', 'visitors', 'source_match',
        'starts_at', 'ends_at', 'days', 'time_from', 'time_to',
        'frequency', 'frequency_days', 'after_close_days', 'hide_after_conversion', 'max_views', 'max_impressions',
    ];

    protected $attributes = [
        'type' => 'modal', 'status' => 'draft', 'priority' => 0, 'image_style' => 'top', 'form' => 'none', 'position' => 'center', 'size' => 'md',
        'color_scheme' => 'default', 'animation' => 'fade', 'show_overlay' => true, 'close_on_overlay' => true, 'show_close' => true,
        'trigger' => 'delay', 'audience' => 'everyone', 'visitors' => 'all', 'frequency' => 'days', 'hide_after_conversion' => true, 'revision' => 1,
        'views_count' => 0, 'clicks_count' => 0, 'closes_count' => 0, 'conversions_count' => 0, 'cta_new_tab' => false,
    ];

    protected function casts(): array
    {
        return [
            'include_paths' => 'array', 'exclude_paths' => 'array', 'devices' => 'array', 'days' => 'array',
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'stats_reset_at' => 'datetime',
            'cta_new_tab' => 'boolean', 'show_overlay' => 'boolean', 'close_on_overlay' => 'boolean', 'show_close' => 'boolean', 'hide_after_conversion' => 'boolean',
            'priority' => 'integer', 'trigger_value' => 'integer', 'auto_close' => 'integer', 'frequency_days' => 'integer', 'after_close_days' => 'integer',
            'max_views' => 'integer', 'max_impressions' => 'integer', 'revision' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Popup $popup) {
            $popup->created_by ??= auth()->id();
        });
        static::saving(function (Popup $popup) {
            // Keep the position consistent with the type (e.g. after switching a popup to a bar).
            if (! array_key_exists((string) $popup->position, self::POSITIONS[$popup->type] ?? [])) {
                $popup->position = array_key_first(self::POSITIONS[$popup->type] ?? self::POSITIONS['modal']);
            }
            foreach (['bg_color', 'text_color', 'button_color'] as $color) {
                if ($popup->{$color} !== null && ! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $popup->{$color})) {
                    $popup->{$color} = null;
                }
            }
        });
        static::saved(fn () => PopupService::flush());
        static::deleted(fn () => PopupService::flush());
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PopupStat::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isBar(): bool
    {
        return $this->type === 'bar';
    }

    public function limitReached(): bool
    {
        return $this->max_impressions && $this->views_count >= $this->max_impressions;
    }

    /** Status, dates, weekdays, hours and campaign limit — everything that depends only on the clock. */
    public function isLive(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        if ($this->status !== 'published' || $this->limitReached()) {
            return false;
        }
        if (($this->starts_at && $this->starts_at->gt($at)) || ($this->ends_at && $this->ends_at->lte($at))) {
            return false;
        }

        $local = $at->copy()->timezone(config('app.display_timezone', config('app.timezone')));
        if ($this->days && ! in_array($local->dayOfWeekIso, array_map('intval', $this->days), true)) {
            return false;
        }

        if ($this->time_from || $this->time_to) {
            $now = $local->format('H:i');
            $from = $this->time_from ?: '00:00';
            $to = $this->time_to ?: '24:00';

            return $from <= $to ? ($now >= $from && $now < $to) : ($now >= $from || $now < $to); // overnight windows such as 22:00–06:00
        }

        return true;
    }

    /** Short state for the dashboard: Draft, Paused, Scheduled, Live, Ended, Limit reached, Outside hours. */
    public function stateLabel(): string
    {
        return match (true) {
            $this->status === 'draft' => 'Draft',
            $this->status === 'paused' => 'Paused',
            $this->limitReached() => 'Limit reached',
            $this->ends_at && $this->ends_at->isPast() => 'Ended',
            $this->starts_at && $this->starts_at->isFuture() => 'Scheduled',
            ! $this->isLive() => 'Outside its days/hours',
            default => 'Live',
        };
    }

    public function stateColor(): string
    {
        return match ($this->stateLabel()) {
            'Live' => 'success',
            'Scheduled', 'Outside its days/hours' => 'info',
            'Paused', 'Limit reached' => 'warning',
            default => 'gray',
        };
    }

    /** Matches a request path against the include / exclude lists. “*” is a wildcard: “/courses/*”. */
    public function matchesPath(string $path): bool
    {
        $path = '/'.trim($path, '/');

        foreach ((array) $this->exclude_paths as $pattern) {
            if (self::pathMatches($pattern, $path)) {
                return false;
            }
        }

        $include = array_filter((array) $this->include_paths, fn ($p) => trim((string) $p) !== '');
        if ($include === []) {
            return true;
        }

        foreach ($include as $pattern) {
            if (self::pathMatches($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    public static function pathMatches(?string $pattern, string $path): bool
    {
        $pattern = trim((string) $pattern);
        if ($pattern === '') {
            return false;
        }
        // Accept full URLs pasted from the browser.
        if (preg_match('~^https?://~i', $pattern)) {
            $pattern = (string) (parse_url($pattern, PHP_URL_PATH) ?: '/');
        }
        $pattern = '/'.trim($pattern, '/');
        if ($pattern === '/*') {
            return true;
        }
        $regex = '~^'.str_replace('\*', '.*', preg_quote($pattern, '~')).'$~i';

        return (bool) preg_match($regex, $path);
    }

    public function matchesAudience(?User $user): bool
    {
        return match ($this->audience) {
            'guests' => $user === null,
            'signed_in' => $user !== null,
            'members' => $user?->isMember() ?? false,
            'non_members' => $user !== null && ! $user->isMember(),
            default => true,
        };
    }

    /** Safe link: internal paths, http(s), mailto, tel and on-page anchors only. */
    public function href(): ?string
    {
        $url = trim((string) $this->cta_url);
        if ($url === '') {
            return null;
        }
        if (str_starts_with($url, '#') || str_starts_with($url, '/') || preg_match('~^(https?:|mailto:|tel:)~i', $url)) {
            return str_starts_with($url, '//') ? null : $url;
        }

        return '/'.ltrim($url, '/');
    }

    /** CSS variables for the “Custom colours” scheme; text on the button is black or white for contrast. */
    public function customStyle(): string
    {
        if ($this->color_scheme !== 'custom') {
            return '';
        }
        $valid = fn ($c) => is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : null;
        $bg = $valid($this->bg_color) ?? '#ffffff';
        $fg = $valid($this->text_color) ?? self::contrast($bg);
        $btn = $valid($this->button_color) ?? $fg;

        return "--popup-bg:{$bg};--popup-fg:{$fg};--popup-btn:{$btn};--popup-btn-fg:".self::contrast($btn).';';
    }

    public static function contrast(string $hex): string
    {
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
        $lin = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $l = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $l > 0.4 ? '#111111' : '#ffffff';
    }

    /** Whether the content sits on a dark or coloured surface (affects button and form styles). */
    public function onColour(): bool
    {
        return in_array($this->color_scheme, ['brand', 'accent'], true)
            || ($this->color_scheme === 'custom' && self::contrast($this->bg_color ?: '#ffffff') === '#ffffff')
            || ($this->media && $this->image_style === 'background' && ! $this->isBar());
    }

    public function ctr(): ?float
    {
        return $this->views_count ? round($this->clicks_count / $this->views_count * 100, 1) : null;
    }

    /** Goal rate: sign-ups when the popup has a form, otherwise button clicks. */
    public function goalRate(): ?float
    {
        $goal = $this->form === 'newsletter' ? $this->conversions_count : $this->clicks_count;

        return $this->views_count ? round($goal / $this->views_count * 100, 1) : null;
    }
}
