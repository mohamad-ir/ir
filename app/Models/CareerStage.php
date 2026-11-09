<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Career stages members choose from (registration, account profile, dashboard).
 * users.career_stage stores the stage key, which never changes once created.
 * One cached list feeds every form, label and filter.
 */
class CareerStage extends Model
{
    use LogsActivity;

    /** Starting list: [key, English name, badge colour]. The first six keys are those of versions before 1.5. */
    public const DEFAULTS = [
        ['student', 'Medical student', 'gray'],
        ['resident', 'Resident / trainee', 'info'],
        ['fellow', 'Fellow', 'primary'],
        ['ophthalmologist', 'Ophthalmologist (specialist / consultant)', 'success'],
        ['other_doctor', 'Doctor in another specialty / GP', 'info'],
        ['optometrist', 'Optometrist', 'warning'],
        ['orthoptist', 'Orthoptist', 'warning'],
        ['ophthalmic_nurse', 'Ophthalmic nurse, technician or photographer', 'warning'],
        ['researcher', 'Researcher / scientist', 'primary'],
        ['other', 'Other', 'gray'],
    ];

    public const COLORS = ['gray' => 'Grey', 'primary' => 'Teal', 'info' => 'Blue', 'success' => 'Green', 'warning' => 'Amber', 'danger' => 'Red'];

    protected $fillable = ['key', 'name', 'color', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (CareerStage $stage) {
            $stage->key = $stage->key ?: static::uniqueKey($stage->name);
        });
        static::updating(function (CareerStage $stage) {
            // The key is what members have stored: it never changes.
            if ($stage->isDirty('key')) {
                $stage->key = $stage->getOriginal('key');
            }
        });
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    public static function uniqueKey(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 32, '') ?: 'stage';
        $key = $base;
        for ($i = 2; static::where('key', $key)->exists(); $i++) {
            $key = $base.'_'.$i;
        }

        return $key;
    }

    public static function flush(): void
    {
        Cache::forget('career-stages');
    }

    /** @return array<int, array{key: string, name: string, color: string, is_active: bool}> ordered list */
    public static function cachedList(): array
    {
        try {
            return Cache::rememberForever('career-stages', fn () => Schema::hasTable('career_stages')
                ? static::query()->orderBy('sort_order')->orderBy('id')->get(['key', 'name', 'color', 'is_active'])->map->only(['key', 'name', 'color', 'is_active'])->all()
                : collect(self::DEFAULTS)->map(fn ($d) => ['key' => $d[0], 'name' => $d[1], 'color' => $d[2], 'is_active' => true])->all());
        } catch (Throwable) {
            return collect(self::DEFAULTS)->map(fn ($d) => ['key' => $d[0], 'name' => $d[1], 'color' => $d[2], 'is_active' => true])->all();
        }
    }

    /**
     * key => name for select fields. Inactive stages are left out, except $keep (a member's current choice),
     * so editing an existing profile never loses it. Names are shown in the dashboard language when translated.
     *
     * @return array<string, string>
     */
    public static function options(bool $activeOnly = true, ?string $keep = null): array
    {
        return collect(static::cachedList())
            ->filter(fn ($s) => ! $activeOnly || $s['is_active'] || $s['key'] === $keep)
            ->mapWithKeys(fn ($s) => [$s['key'] => __($s['name'])])->all();
    }

    /** @return array<int, string> keys a member may choose (plus their current one) */
    public static function selectableKeys(?string $keep = null): array
    {
        return array_keys(static::options(true, $keep));
    }

    public static function labelFor(?string $key): ?string
    {
        if (blank($key)) {
            return null;
        }
        $stage = collect(static::cachedList())->firstWhere('key', $key);

        return $stage ? __($stage['name']) : Str::headline($key);
    }

    public static function colorFor(?string $key): string
    {
        return collect(static::cachedList())->firstWhere('key', $key)['color'] ?? 'gray';
    }

    public function usersCount(): int
    {
        return User::where('career_stage', $this->key)->count();
    }
}
