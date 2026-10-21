<?php

namespace App\Services\Demo;

use App\Models\ActivityLog;
use App\Models\Media;
use App\Services\Media\MediaService;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Relations\Relation;
use Throwable;

/** Tracks the sample content created at installation so it can be removed in one step. */
class DemoContent
{
    public static function remember(array $items): void
    {
        $existing = (array) Settings::get('system.demo_items', []);
        Settings::set('system.demo_items', array_values(array_unique([...$existing, ...$items])));
    }

    public static function items(): array
    {
        return (array) Settings::get('system.demo_items', []);
    }

    public static function present(): bool
    {
        return count(static::items()) > 0;
    }

    /** @return array<string, int> */
    public static function summary(): array
    {
        return collect(static::items())->map(fn ($i) => explode(':', $i)[0])->countBy()->all();
    }

    public static function remove(): int
    {
        $removed = 0;
        $media = [];

        foreach (static::items() as $item) {
            [$type, $id] = explode(':', $item);
            $class = Relation::getMorphedModel($type);
            if (! $class) {
                continue;
            }
            if ($type === 'media') {
                $media[] = (int) $id;

                continue;
            }
            try {
                $model = method_exists($class, 'withTrashed') ? $class::withTrashed()->find($id) : $class::find($id);
                if ($model) {
                    method_exists($model, 'forceDelete') ? $model->forceDelete() : $model->delete();
                    $removed++;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        if (in_array((int) Settings::get('doctor.photo_id'), $media, true)) {
            Settings::set('doctor.photo_id', null);
        }

        foreach (Media::whereIn('id', $media)->get() as $m) {
            app(MediaService::class)->delete($m);
            $removed++;
        }

        Settings::set('system.demo_items', []);
        ActivityLog::record('demo_removed', "Sample content removed ({$removed} items)");

        return $removed;
    }
}
