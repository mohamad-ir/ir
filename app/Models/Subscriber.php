<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    use HasFactory;

    public const INTERESTS = [
        'weekly_case' => 'Case of the Week',
        'articles' => 'New articles & videos',
        'exam' => 'FRCS / FRCOphth preparation',
        'courses' => 'Future courses (register interest)',
        'webinars' => 'Future webinars & teaching sessions',
    ];

    protected $fillable = ['email', 'name', 'status', 'token', 'interests', 'source', 'user_id', 'confirmed_at', 'unsubscribed_at'];

    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'status' => SubscriberStatus::class,
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Subscriber $s) {
            $s->token = $s->token ?: Str::random(64);
            $s->email = mb_strtolower(trim($s->email));
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::Subscribed->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', $this->token);
    }
}
