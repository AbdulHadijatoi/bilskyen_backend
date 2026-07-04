<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerMarketingCampaign extends Model
{
    protected $fillable = [
        'dealer_id',
        'created_by_user_id',
        'name',
        'type',
        'audience',
        'subject',
        'body',
        'status',
        'scheduled_at',
        'sent_count',
        'meta',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
