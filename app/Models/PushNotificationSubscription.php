<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationSubscription extends Model
{
    use HasFactory;

    protected $table = 'push_notification_subscriptions';

    protected $fillable = [
        'endpoint',
        'p256dh',
        'auth',
        'user_id',
        'device_id',
        'expiration_time',
        'is_active',
    ];

    protected $casts = [
        'expiration_time' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get user for this subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


