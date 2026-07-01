<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedEnquirySession extends Model
{
    protected $fillable = [
        'session_id',
        'vehicle_id',
        'dealer_id',
        'form_data',
        'last_activity_at',
        'recovered_at',
        'enquiry_id',
    ];

    protected $casts = [
        'form_data' => 'array',
        'last_activity_at' => 'datetime',
        'recovered_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
