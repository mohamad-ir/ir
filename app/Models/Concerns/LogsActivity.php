<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            if ($event === 'restored' && ! method_exists(static::class, 'restored')) {
                continue;
            }

            static::registerModelEvent($event, function ($model) use ($event) {
                if ($event === 'updated' && array_diff(array_keys($model->getChanges()), ['updated_at', 'views_count', 'reveals_count', 'guesses_count', 'correct_guesses_count', 'plays_count', 'completions_count', 'attempts_count', 'correct_count', 'last_login_at', 'remember_token']) === []) {
                    return;
                }

                if (! auth()->check()) {
                    return;
                }

                $label = class_basename($model);
                $name = $model->title ?? $model->name ?? $model->subject ?? $model->email ?? ('#'.$model->getKey());

                ActivityLog::record($event, sprintf('%s %s “%s”', $label, $event, $name), $model, $event === 'updated' ? ['changed' => array_values(array_diff(array_keys($model->getChanges()), ['updated_at']))] : []);
            });
        }
    }
}
