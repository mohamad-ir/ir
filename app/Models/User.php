<?php

namespace App\Models;

use App\Enums\CareerStage;
use App\Models\Concerns\LogsActivity;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar, MustVerifyEmail
{
    use HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, LogsActivity, Notifiable;

    /** Roles that may sign in to the administration dashboard. */
    public const STAFF_ROLES = ['super_admin', 'administrator', 'editor', 'author', 'contributor'];

    protected $fillable = [
        'name', 'email', 'password', 'title', 'career_stage', 'specialty', 'institution',
        'country', 'bio', 'avatar_path', 'is_active', 'last_login_at', 'stripe_customer_id',
    ];

    protected $hidden = ['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'career_stage' => CareerStage::class,
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->isStaff();
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(self::STAFF_ROLES);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarUrl();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim(preg_replace('/^(dr|prof|mr|mrs|ms|miss)\.?\s+/i', '', $this->name))))
            ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }

    public function displayName(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->name);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function questionAttempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function webinarRegistrations(): HasMany
    {
        return $this->hasMany(WebinarRegistration::class);
    }

    /** The subscription currently granting member access, if any. */
    public function activeSubscription(): ?Subscription
    {
        if (! array_key_exists('activeSubscription', $this->relations)) {
            $this->setRelation('activeSubscription', $this->subscriptions()->grantingAccess()->with('plan')->latest('id')->first());
        }

        return $this->getRelation('activeSubscription');
    }

    public function isMember(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function forgetMembership(): void
    {
        $this->unsetRelation('activeSubscription');
    }

    public function hasBookmarked($model): bool
    {
        return $this->bookmarks()->where('bookmarkable_type', $model->getMorphClass())->where('bookmarkable_id', $model->getKey())->exists();
    }
}
