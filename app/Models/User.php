<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified',
        'phone',
        'address',
        'role',
        'image',
        'banned',
        'ban_reason',
        'ban_expires',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verified' => 'boolean',
            'banned' => 'boolean',
            'ban_expires' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get enquiries for this user
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Get notifications read by this user
     */
    public function readNotifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'notification_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Get push notification subscriptions for this user
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushNotificationSubscription::class);
    }

    /**
     * Get sessions for this user
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Get accounts (OAuth/password) for this user
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get status attribute (active or banned)
     */
    public function getStatusAttribute(): string
    {
        return $this->banned ? 'banned' : 'active';
    }
}
