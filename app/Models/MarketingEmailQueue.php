<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingEmailQueue extends Model
{
    protected $table = 'marketing_email_queue';

    protected $fillable = [
        'enquiry_id',
        'lead_id',
        'dealer_id',
        'recipient_email',
        'sequence_key',
        'step_key',
        'meta',
        'status',
        'scheduled_at',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'meta' => 'array',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
