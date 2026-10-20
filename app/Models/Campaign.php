<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use LogsActivity;

    protected $fillable = ['subject', 'preheader', 'body', 'audience', 'status', 'scheduled_at', 'started_at', 'finished_at', 'recipients_count', 'sent_count', 'failed_count', 'opens_count', 'clicks_count', 'created_by'];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function openRate(): ?float
    {
        return $this->sent_count ? round($this->opens_count / $this->sent_count * 100, 1) : null;
    }

    public function clickRate(): ?float
    {
        return $this->sent_count ? round($this->clicks_count / $this->sent_count * 100, 1) : null;
    }
}
