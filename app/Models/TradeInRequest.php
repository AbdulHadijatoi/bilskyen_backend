<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeInRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_OFFERED = 'offered';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'dealer_id',
        'vehicle_id',
        'lead_id',
        'enquiry_id',
        'licence_plate',
        'kilometers',
        'expected_price',
        'condition_notes',
        'appraisal_status',
        'offered_value_cents',
        'appraisal_notes',
        'appraised_at',
    ];

    protected $casts = [
        'appraised_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
