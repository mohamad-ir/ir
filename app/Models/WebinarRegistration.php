<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebinarRegistration extends Model
{
    public const SOURCES = ['free' => 'Free registration', 'purchase' => 'Ticket purchased', 'membership' => 'Membership', 'admin' => 'Added by staff'];

    protected $fillable = [
        'webinar_id', 'user_id', 'source', 'order_id', 'reminder_day_sent_at', 'reminder_hour_sent_at',
        'recording_sent_at', 'joined_at', 'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_day_sent_at' => 'datetime',
            'reminder_hour_sent_at' => 'datetime',
            'recording_sent_at' => 'datetime',
            'joined_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $sync = fn (WebinarRegistration $r) => Webinar::withTrashed()->whereKey($r->webinar_id)
            ->update(['registrations_count' => static::where('webinar_id', $r->webinar_id)->whereNull('canceled_at')->count()]);
        static::saved($sync);
        static::deleted($sync);
    }

    public function webinar(): BelongsTo
    {
        return $this->belongsTo(Webinar::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isActive(): bool
    {
        return $this->canceled_at === null;
    }
}
