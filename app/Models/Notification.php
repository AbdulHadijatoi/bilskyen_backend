<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'target_roles',
        'sent',
        'scheduled_at',
        'metadata',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'sent' => 'boolean',
        'scheduled_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get users who have read this notification
     */
    public function reads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Get reads count attribute
     */
    public function getReadsCountAttribute(): int
    {
        return $this->reads()->count();
    }

    /**
     * Check if notification is read by a specific user
     */
    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }
}


