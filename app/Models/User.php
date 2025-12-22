<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified',
        'phone',
        'address',
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

    /**
     * Get initials attribute - Generate initials from user name
     * First letter of first name + first letter of last name (if 2+ words)
     * Or first 2 characters if single word
     */
    public function getInitialsAttribute(): string
    {
        $userName = $this->name ?? '';
        if (empty(trim($userName))) {
            return 'U';
        }

        $names = array_values(array_filter(array_map('trim', explode(' ', trim($userName))), function($n) {
            return $n !== '';
        }));

        if (count($names) >= 2) {
            $firstInitial = substr($names[0], 0, 1);
            $lastInitial = substr($names[count($names) - 1], 0, 1);
            return strtoupper($firstInitial . $lastInitial);
        } else if (count($names) === 1 && strlen($names[0]) > 0) {
            $name = $names[0];
            if (strlen($name) >= 2) {
                return strtoupper(substr($name, 0, 2));
            } else {
                return strtoupper($name . $name); // Repeat single char
            }
        }

        return 'U';
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'roles' => $this->roles->pluck('name')->toArray(),
            'email' => $this->email,
        ];
    }
}
