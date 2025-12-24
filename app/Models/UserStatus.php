<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserStatus extends Model
{
    use HasFactory;

    public const ACTIVE = 1;
    public const INACTIVE = 2;
    public const SUSPENDED = 3;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Get users with this status
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'status_id');
    }
}
