<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'buyer_user_id',
        'dealer_id',
        'assigned_user_id',
        'lead_stage_id',
        'source_id',
        'last_activity_at',
        'created_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get vehicle for this lead
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get buyer user for this lead
     */
    public function buyerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    /**
     * Get dealer for this lead
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * Get assigned user for this lead
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get lead stage for this lead
     */
    public function leadStage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class);
    }

    /**
     * Get source for this lead
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Get stage history for this lead
     */
    public function stageHistory(): HasMany
    {
        return $this->hasMany(LeadStageHistory::class);
    }

    /**
     * Get chat threads for this lead
     */
    public function chatThreads(): HasMany
    {
        return $this->hasMany(ChatThread::class);
    }
}
