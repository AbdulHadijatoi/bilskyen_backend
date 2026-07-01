<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerSyndicationSetting extends Model
{
    protected $fillable = [
        'dealer_id',
        'provider_key',
        'enabled',
        'field_mapping',
        'last_sync_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'field_mapping' => 'array',
        'last_sync_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
