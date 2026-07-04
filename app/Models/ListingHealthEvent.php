<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingHealthEvent extends Model
{
    protected $fillable = [
        'vehicle_id',
        'dealer_id',
        'fix_type',
        'issue_key',
        'before_metrics',
        'after_metrics',
        'fixed_at',
        'measured_at',
        'status',
        'changed_by_user_id',
    ];

    protected $casts = [
        'before_metrics' => 'array',
        'after_metrics' => 'array',
        'fixed_at' => 'datetime',
        'measured_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
