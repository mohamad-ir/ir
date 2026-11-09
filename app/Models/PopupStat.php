<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Daily popup figures per device type. */
class PopupStat extends Model
{
    public $timestamps = false;

    protected $fillable = ['popup_id', 'date', 'device', 'views', 'clicks', 'closes', 'conversions'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function popup(): BelongsTo
    {
        return $this->belongsTo(Popup::class);
    }
}
