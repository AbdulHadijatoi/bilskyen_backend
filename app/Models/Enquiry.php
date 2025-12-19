<?php

namespace App\Models;

use App\Traits\HasSerialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    use HasFactory, HasSerialNumber;

    protected $fillable = [
        'serial_no',
        'subject',
        'message',
        'type',
        'status',
        'source',
        'contact_id',
        'user_id',
        'vehicle_id',
    ];

    protected $casts = [
        'serial_no' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get contact for this enquiry
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get user for this enquiry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get vehicle for this enquiry
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}

