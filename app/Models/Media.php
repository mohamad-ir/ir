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
        'is_deidentified', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['variants' => 'array', 'is_deidentified' => 'boolean', 'size' => 'integer'];
    }

    /** Responsive widths generated for every raster image (WebP). */
    public const VARIANT_WIDTHS = ['thumb' => 400, 'md' => 960, 'lg' => 1600];

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

    public function srcset(): ?string
    {
        if (empty($this->variants)) {
            return null;
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
