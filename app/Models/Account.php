<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'provider_id',
        'access_token',
        'refresh_token',
        'id_token',
        'expires_at',
        'password',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'id_token',
        'password',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get user for this account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

