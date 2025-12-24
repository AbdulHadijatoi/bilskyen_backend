<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get user status for this user
     */
    public function userStatus(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class, 'status_id');
    }

    /**
     * Get dealer users (dealer memberships) for this user
     */
    public function dealerUsers(): HasMany
    {
        return $this->hasMany(DealerUser::class);
    }

    /**
     * Get dealers this user belongs to
     */
    public function dealers(): BelongsToMany
    {
        return $this->belongsToMany(Dealer::class, 'dealer_users')
            ->withPivot('role_id')
            ->withTimestamps('created_at');
    }

    /**
     * Get vehicles created by this user
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get favorites for this user
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get saved searches for this user
     */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    /**
     * Get leads where this user is the buyer
     */
    public function buyerLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'buyer_user_id');
    }

    /**
     * Get leads assigned to this user
     */
    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_user_id');
    }

    /**
     * Get chat messages sent by this user
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    /**
     * Get price history records changed by this user
     */
    public function priceHistoryChanges(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'changed_by_user_id');
    }

    /**
     * Get view logs for this user
     */
    public function viewLogs(): HasMany
    {
        return $this->hasMany(ListingViewsLog::class);
    }

    /**
     * Get user plan overrides for this user
     */
    public function planOverrides(): HasMany
    {
        return $this->hasMany(UserPlanOverride::class);
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
