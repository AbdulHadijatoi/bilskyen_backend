<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class DealerUser extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 1;
    public const ROLE_MANAGER = 2;
    public const ROLE_STAFF = 3;

    public $timestamps = false;

    protected $fillable = [
        'dealer_id',
        'user_id',
        'role_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get dealer for this dealer user
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * Get user for this dealer user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get role for this dealer user
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
