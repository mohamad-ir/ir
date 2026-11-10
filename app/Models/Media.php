<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class Media extends Model
{
    use LogsActivity;

    protected $table = 'media';

    protected $fillable = [
        'folder_id', 'disk', 'path', 'filename', 'original_name', 'mime_type', 'extension', 'size',
        'width', 'height', 'title', 'alt', 'caption', 'credit', 'description', 'variants', 'hash',
        'is_deidentified', 'uploaded_by', 'quality', 'optimized_profile', 'variants_version',
    ];

    protected function casts(): array
    {
        return ['variants' => 'array', 'is_deidentified' => 'boolean', 'size' => 'integer', 'variants_version' => 'integer'];
    }

    /**
     * Responsive widths generated for every raster image (WebP). xl serves large and high-density
     * (Retina) screens; zooming in a viewer loads the untouched original.
     */
    public const VARIANT_WIDTHS = ['thumb' => 400, 'md' => 960, 'lg' => 1600, 'xl' => 2560];

    /** Bumped whenever the optimisation pipeline changes; older images are re-optimised in the background. */
    public const VARIANTS_VERSION = 2;

    /**
     * WebP quality per profile and variant (100 = lossless).
     *  - standard: photographs and illustrations.
     *  - high: clinical and diagnostic images (fundus photos, OCT scans, angiography) and graphics with text.
     *  - lossless: pixel-exact copies for images where every detail matters.
     */
    public const QUALITY = [
        'standard' => ['thumb' => 80, 'md' => 84, 'lg' => 86, 'xl' => 86],
        'high' => ['thumb' => 85, 'md' => 92, 'lg' => 92, 'xl' => 92],
        'lossless' => ['thumb' => 85, 'md' => 100, 'lg' => 100, 'xl' => 100],
    ];

    /**
     * Images shown in OCT cases and questions are clinical: those optimised with the automatic
     * standard profile are queued for re-optimisation with the high profile.
     */
    public static function markClinical(array $ids): void
    {
        $ids = array_filter($ids);
        if ($ids) {
            static::whereIn('id', $ids)->whereNull('quality')->where('optimized_profile', 'standard')->update(['variants_version' => 0]);
        }
    }

    /** True when the image is shown in an OCT case or a question. */
    public function isClinical(): bool
    {
        if (! $this->exists) {
            return false;
        }
        $id = (int) $this->id;
        if (Question::withTrashed()->where('media_id', $id)->exists() || ClinicalCase::withTrashed()->where('featured_media_id', $id)->exists()) {
            return true;
        }

        // Narrow with LIKE, then check the decoded image lists exactly.
        return ClinicalCase::withTrashed()
            ->where(fn ($q) => $q->where('images', 'like', '%'.$id.'%')->orWhere('comparison', 'like', '%'.$id.'%'))
            ->get(['id', 'images', 'comparison', 'featured_media_id'])
            ->contains(fn (ClinicalCase $c) => in_array($id, $c->clinicalMediaIds(), true));
    }

    /** @return array<string, string> */
    public static function qualityOptions(): array
    {
        return [
            'auto' => __('Automatic (recommended)'),
            'standard' => __('Standard — photographs'),
            'high' => __('High — clinical and diagnostic images'),
            'lossless' => __('Lossless — maximum detail, larger files'),
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function url(?string $variant = null): string
    {
        $path = $variant ? ($this->variants[$variant] ?? $this->path) : $this->path;

        return Storage::disk($this->disk)->url($path).($this->updated_at ? '?v='.$this->updated_at->timestamp : '');
    }

    public function thumbUrl(): string
    {
        return $this->url(isset($this->variants['thumb']) ? 'thumb' : null);
    }

    /** URL of the largest optimised version (falls back to the original). */
    public function largestUrl(): string
    {
        foreach (array_reverse(array_keys(self::VARIANT_WIDTHS)) as $name) {
            if (isset($this->variants[$name]) && $name !== 'thumb') {
                return $this->url($name);
            }
        }

        return $this->url();
    }

    public function srcset(): ?string
    {
        if (empty($this->variants) || $this->mime_type === 'image/gif') {
            return null; // GIFs keep their animation, so the original is always served
        }

        $parts = [];
        foreach (self::VARIANT_WIDTHS as $name => $width) {
            if (isset($this->variants[$name])) {
                $parts[] = $this->url($name).' '.min($width, $this->width ?: $width).'w';
            }
        }

        return $parts ? implode(', ', $parts) : null;
    }

    public function altText(): string
    {
        return $this->alt ?: ($this->title ?: pathinfo($this->original_name, PATHINFO_FILENAME));
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    public function dimensions(): ?string
    {
        return $this->width ? "{$this->width} × {$this->height}" : null;
    }

    public function pickerLabel(): string
    {
        $label = e($this->title ?: $this->original_name);

        if ($this->isImage()) {
            return '<span style="display:flex;align-items:center;gap:.6rem"><img src="'.e($this->thumbUrl()).'" alt="" style="width:2.5rem;height:2.5rem;object-fit:cover;border-radius:.375rem;flex-shrink:0"> <span>'.$label.'</span></span>';
        }

        return $label;
    }
}
