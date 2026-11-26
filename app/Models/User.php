<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'division_id',
        'name',
        'email',
        'google_id',
        'avatar',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'role',
        'job_title',
        'is_active',
        'notification_preferences',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'notification_preferences' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function headedDivisions(): HasMany
    {
        return $this->hasMany(Division::class, 'head_id');
    }

    public function meetingsCreated(): HasMany
    {
        return $this->hasMany(Meeting::class, 'created_by');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class, 'pic_id');
    }

    public function quickNotes(): HasMany
    {
        return $this->hasMany(QuickNote::class, 'user_id');
    }

    public function quickNoteMentions(): HasMany
    {
        return $this->hasMany(QuickNoteMention::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canWriteMinutes(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Notulis], true);
    }

    public function wantsEmailNotifications(?string $category = null): bool
    {
        if (($this->notification_preferences['email'] ?? true) === false) {
            return false;
        }

        return $category === null || $this->wantsNotificationCategory($category);
    }

    public function wantsInAppNotifications(?string $category = null): bool
    {
        if (($this->notification_preferences['in_app'] ?? true) === false) {
            return false;
        }

        return $category === null || $this->wantsNotificationCategory($category);
    }

    public function wantsNotificationCategory(string $category): bool
    {
        return (bool) ($this->notification_preferences['categories'][$category] ?? true);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }
}
